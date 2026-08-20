// شاشة المحادثة مع المساعد الذكي — هوية خضراء (#88D498)، خط Cairo، وخلفية بنمط المتجر.
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../../core/di/service_locator.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/widgets/app_network_image.dart';
import '../../domain/entities/chat_message.dart';
import '../cubit/ai_controller_cubit.dart';
import '../widgets/chat_bubble.dart';
import '../widgets/suggested_products_grid.dart';
import '../widgets/typing_indicator.dart';
import '../widgets/voice_mic_button.dart';
import '../../../shop/data/models/product_model.dart';
import '../../../shop/domain/entities/cart_item.dart';
import '../../../shop/presentation/manager/cart_cubit.dart';
import '../../../shop/presentation/pages/product_details_screen.dart';
import '../../../shop/presentation/widgets/main_shell_scope.dart';

// ── هوية المحادثة (أخضر معتمد) ───────────────────────────────────────────────
const _kMintBrand = Color(0xFF88D498);
const _kMintDark = Color(0xFF2D6A4F);
const _kMintPaleBg = Color(0xFFE8F8ED);
const _kMintGradientA = Color(0xFFB8E8C8);
const _kMintGradientB = Color(0xFF88D498);
const _kMintGradientC = Color(0xFF6BC489);
const _kChatSurface = Color(0xFFFAFDFB);

TextStyle _cairo(
  double fontSize, {
  FontWeight fontWeight = FontWeight.w500,
  Color? color,
  double? height,
  FontStyle? fontStyle,
}) =>
    GoogleFonts.cairo(
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      height: height,
      fontStyle: fontStyle,
    );

/// خلفية بيضاء مع نمط فواكه/منظفات خفيف جداً
class _ChatBackdrop extends StatelessWidget {
  final Widget child;

  const _ChatBackdrop({required this.child});

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        const ColoredBox(color: _kChatSurface),
        LayoutBuilder(
          builder: (context, constraints) {
            return CustomPaint(
              size: Size(constraints.maxWidth, constraints.maxHeight),
              painter: _FaintStorePatternPainter(),
            );
          },
        ),
        child,
      ],
    );
  }
}

class _FaintStorePatternPainter extends CustomPainter {
  static const _glyphs = ['🍊', '🍋', '🧼', '🧴', '🍇', '🫧', '🍎', '🧽'];

  @override
  void paint(Canvas canvas, Size size) {
    if (size.width <= 0 || size.height <= 0) return;
    for (var i = 0; i < 28; i++) {
      final x = ((i * 47.0 + 11) % (size.width - 28)) + 8;
      final y = ((i * 61.0 + 19) % (size.height - 28)) + 8;
      final tp = TextPainter(
        text: TextSpan(
          text: _glyphs[i % _glyphs.length],
          style: TextStyle(
            fontSize: 16 + (i % 4) * 3.0,
            color: _kMintBrand.withValues(alpha: 0.045),
          ),
        ),
        textDirection: TextDirection.ltr,
      )..layout();
      tp.paint(canvas, Offset(x, y));
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

Future<void> _openCheckoutSmart(BuildContext context) async {
  final shell = context.findAncestorWidgetOfExactType<MainShellScope>();
  if (shell != null) {
    await shell.openCheckout();
  } else {
    await Navigator.of(context).pushNamed<int>(AppRouter.checkout);
  }
}

void _showChatOptionsMenu(BuildContext hostContext) {
  showModalBottomSheet<void>(
    context: hostContext,
    backgroundColor: Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (sheetContext) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 20),
          ListTile(
            leading: const Icon(Icons.delete_outline_rounded,
                color: Color(0xFFFF4757)),
            title:
                const Text('مسح المحادثة', textDirection: TextDirection.rtl),
            onTap: () {
              Navigator.pop(sheetContext);
              hostContext.read<AiControllerCubit>().clearConversation();
            },
          ),
        ],
      ),
    ),
  );
}

class ChatScreen extends StatelessWidget {
  static const routeName = '/chat';

  /// يربط [Hero] بين زر المساعد في الشريط السفلي وميز الميكروفون عند فتح الشات من الـ shell.
  final bool useHeroMic;

  const ChatScreen({
    super.key,
    this.useHeroMic = true,
  });

  @override
  Widget build(BuildContext context) {
    // CartCubit مُقدَّم من مستوى التطبيق (main.dart)
    return BlocProvider<AiControllerCubit>(
      create: (ctx) => ServiceLocator.instance.createAiController(
        cartCubit: ctx.read<CartCubit>(),
      )..initConversation(),
      child: _ChatView(useHeroMic: useHeroMic),
    );
  }
}

// ── الشاشة الداخلية ──────────────────────────────────────────────────────────
class _ChatView extends StatefulWidget {
  final bool useHeroMic;

  const _ChatView({
    required this.useHeroMic,
  });

  @override
  State<_ChatView> createState() => _ChatViewState();
}

class _ChatViewState extends State<_ChatView> {
  final _textController = TextEditingController();
  final _scrollController = ScrollController();
  bool _showTextField = false;

  @override
  void dispose() {
    _textController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final baseTheme = Theme.of(context);
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Theme(
        data: baseTheme.copyWith(
          textTheme: GoogleFonts.cairoTextTheme(baseTheme.textTheme),
        ),
        child: Scaffold(
          backgroundColor: Colors.transparent,
          appBar: _ChatAppBar(onOpenOptions: () => _showChatOptionsMenu(context)),
          body: _ChatBackdrop(
            child: MultiBlocListener(
              listeners: [
            // ── إشعار السلة (من CartCubit) ──────────────────────────────
            BlocListener<CartCubit, CartState>(
              listenWhen: (prev, curr) =>
                  curr.lastAddedProductName != null &&
                  prev.lastAddedProductName != curr.lastAddedProductName,
              listener: (context, state) {
                final name = state.lastAddedProductName!;
                ScaffoldMessenger.of(context)
                  ..hideCurrentSnackBar()
                  ..showSnackBar(
                    SnackBar(
                      content: Row(
                        children: [
                          const Icon(Icons.shopping_cart_checkout_rounded,
                              color: Colors.white, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'أُضيف "$name" للسلة ✓',
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        ],
                      ),
                      backgroundColor: const Color(0xFF4CAF50),
                      behavior: SnackBarBehavior.floating,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14)),
                      margin: const EdgeInsets.all(16),
                      duration: const Duration(seconds: 3),
                      action: SnackBarAction(
                        label: 'عرض السلة',
                        textColor: Colors.white,
                        onPressed: () => _CartSheet.show(context),
                      ),
                    ),
                  );
                // امسح الإشعار لإعادة الاستماع للإضافة التالية
                context.read<CartCubit>().dismissNotification();
              },
            ),

            // ── التنقل لصفحة الدفع (من طلب المساعد الذكي) ───────────
            BlocListener<AiControllerCubit, AiControllerState>(
              listenWhen: (prev, curr) =>
                  !prev.checkoutRequested && curr.checkoutRequested,
              listener: (context, state) {
                context.read<AiControllerCubit>().clearCheckoutRequest();
                _openCheckoutSmart(context);
              },
            ),

            // ── رسائل المحادثة الجديدة (تمرير للأسفل) ──────────────────
            BlocListener<AiControllerCubit, AiControllerState>(
              listenWhen: (prev, curr) =>
                  prev.messages.length != curr.messages.length,
              listener: (context, state) {
                if (state.messages.isNotEmpty) _scrollToBottom();
              },
            ),

            // ── رسائل الخطأ ──────────────────────────────────────────────
            BlocListener<AiControllerCubit, AiControllerState>(
              listenWhen: (prev, curr) =>
                  curr.errorMessage != null &&
                  prev.errorMessage != curr.errorMessage,
              listener: (context, state) {
                // خطأ رسالة الترحيب يُعرض inline — لا نُكرر بـ SnackBar
                if (state.messages.isEmpty) return;
                ScaffoldMessenger.of(context)
                  ..hideCurrentSnackBar()
                  ..showSnackBar(
                    SnackBar(
                      content: Text(
                        state.errorMessage!.split('\n').first,
                        style: const TextStyle(color: Colors.white),
                      ),
                      backgroundColor: const Color(0xFFFF4757),
                      behavior: SnackBarBehavior.floating,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14)),
                      margin: const EdgeInsets.all(16),
                      action: SnackBarAction(
                        label: 'حسناً',
                        textColor: Colors.white,
                        onPressed: () =>
                            context.read<AiControllerCubit>().dismissError(),
                      ),
                    ),
                  );
              },
            ),
          ],
          child: BlocBuilder<AiControllerCubit, AiControllerState>(
            builder: (context, state) {
              return Column(
                children: [
                  Expanded(
                    child: _MessagesList(
                      messages: state.messages,
                      isThinking: state.isThinking,
                      partialSpeechText: state.partialSpeechText,
                      scrollController: _scrollController,
                      errorMessage: state.errorMessage,
                      onAddToCart: (product) =>
                          context.read<AiControllerCubit>().addToCart(product),
                      onOpenProduct: (product) {
                        Navigator.of(context).pushNamed(
                          AppRouter.productDetails,
                          arguments: ProductDetailsArgs(product: product),
                        );
                      },
                    ),
                  ),
                  _BottomBar(
                    useHeroMic: widget.useHeroMic,
                    status: state.status,
                    showTextField: _showTextField,
                    textController: _textController,
                    onMicTap: () {
                      final cubit = context.read<AiControllerCubit>();
                      if (state.isListening) {
                        cubit.stopVoiceInput();
                      } else {
                        cubit.startVoiceInput();
                      }
                    },
                    onTextSend: () {
                      final text = _textController.text.trim();
                      if (text.isEmpty) return;
                      context.read<AiControllerCubit>().sendTextMessage(text);
                      _textController.clear();
                    },
                    onToggleInput: () =>
                        setState(() => _showTextField = !_showTextField),
                  ),
                ],
              );
            },
          ),
        ),
        ),
      ),
    ),
    );
  }
}

// ── AppBar المحادثة (RTL: رجوع يمين، سلة + إجراءات يسار) ─────────────────────
class _ChatAppBar extends StatelessWidget implements PreferredSizeWidget {
  final VoidCallback onOpenOptions;

  const _ChatAppBar({required this.onOpenOptions});

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    return AppBar(
      leading: IconButton(
        icon: const Icon(Icons.arrow_back_ios_new,
            color: Colors.white, size: 20),
        onPressed: () => Navigator.pop(context),
      ),
      centerTitle: true,
      title: Text(
        'مساعد روعة الخمسة',
        style: _cairo(18, fontWeight: FontWeight.w800, color: Colors.white),
      ),
      actions: [
        const _ChatAppBarCartButton(),
        IconButton(
          icon: const Icon(Icons.more_vert_rounded,
              color: Colors.white, size: 26),
          onPressed: onOpenOptions,
        ),
      ],
      flexibleSpace: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              _kMintGradientA,
              _kMintGradientB,
              _kMintGradientC,
            ],
            begin: Alignment.topRight,
            end: Alignment.bottomLeft,
          ),
        ),
      ),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(28)),
      ),
      backgroundColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0,
      foregroundColor: Colors.white,
      iconTheme: const IconThemeData(color: Colors.white),
    );
  }
}

class _ChatAppBarCartButton extends StatelessWidget {
  const _ChatAppBarCartButton();

  @override
  Widget build(BuildContext context) {
    final cartCount = context.watch<CartCubit>().state.count;
    return Stack(
      clipBehavior: Clip.none,
      alignment: Alignment.center,
      children: [
        IconButton(
          icon: const Icon(Icons.shopping_cart_outlined,
              color: Colors.white, size: 26),
          onPressed: () => _CartSheet.show(context),
        ),
        if (cartCount > 0)
          PositionedDirectional(
            top: 8,
            end: 8,
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 250),
              transitionBuilder: (child, anim) =>
                  ScaleTransition(scale: anim, child: child),
              child: Container(
                key: ValueKey(cartCount),
                width: 18,
                height: 18,
                decoration: const BoxDecoration(
                  color: Color(0xFFFF4757),
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    cartCount > 99 ? '99+' : '$cartCount',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 9,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

// ── قائمة الرسائل ─────────────────────────────────────────────────────────────
class _MessagesList extends StatelessWidget {
  final List<ChatMessage> messages;
  final bool isThinking;
  final String partialSpeechText;
  final ScrollController scrollController;
  final void Function(ProductModel) onAddToCart;
  final void Function(ProductModel) onOpenProduct;
  final String? errorMessage;

  const _MessagesList({
    required this.messages,
    required this.isThinking,
    required this.partialSpeechText,
    required this.scrollController,
    required this.onAddToCart,
    required this.onOpenProduct,
    this.errorMessage,
  });

  @override
  Widget build(BuildContext context) {
    if (messages.isEmpty && !isThinking) {
      return _EmptyState(loadError: errorMessage);
    }

    return ListView.builder(
      controller: scrollController,
      padding: const EdgeInsets.symmetric(vertical: 16),
      itemCount: _itemCount,
      itemBuilder: (context, index) => _buildItem(context, index),
    );
  }

  int get _itemCount {
    int count = messages.length;
    if (isThinking) count++;
    if (partialSpeechText.isNotEmpty) count++;
    return count;
  }

  Widget _buildItem(BuildContext context, int index) {
    if (index < messages.length) {
      final msg = messages[index];
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ChatBubble(message: msg),
          if (!msg.isUser && msg.suggestedProducts.isNotEmpty)
            SuggestedProductsGrid(
              products: msg.suggestedProducts,
              onAddToCart: onAddToCart,
              onOpen: onOpenProduct,
            ),
        ],
      );
    }

    int offset = messages.length;

    // النص المؤقت للصوت
    if (partialSpeechText.isNotEmpty && index == offset) {
      return _PartialSpeechBubble(text: partialSpeechText);
    }
    if (partialSpeechText.isNotEmpty) offset++;

    // مؤشر الكتابة
    if (isThinking && index == offset) {
      return const _ThinkingIndicator();
    }

    return const SizedBox.shrink();
  }
}

// ── الحالة الفارغة ────────────────────────────────────────────────────────────
class _EmptyState extends StatelessWidget {
  final String? loadError;
  const _EmptyState({this.loadError});

  @override
  Widget build(BuildContext context) {
    final hasError = loadError != null && loadError!.isNotEmpty;
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 90,
              height: 90,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: hasError
                      ? [const Color(0xFFFFEBEB), const Color(0xFFFFD4D4)]
                      : [const Color(0xFFE8F8ED), const Color(0xFFC8ECD4)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                shape: BoxShape.circle,
              ),
              child: Center(
                child: Text(
                  hasError ? '⚠️' : '🛍️',
                  style: const TextStyle(fontSize: 42),
                ),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              hasError ? 'تعذّر الاتصال بالمساعد' : 'مرحباً بك في روعة الخمسة!',
              textAlign: TextAlign.center,
              style: _cairo(18,
                  fontWeight: FontWeight.bold, color: _kMintDark),
            ),
            const SizedBox(height: 8),
            Text(
              hasError
                  ? 'تأكد من اتصالك بالإنترنت ثم أعد المحاولة'
                  : 'اضغط على الميكروفون وتحدث،\nأو اكتب سؤالك في الأسفل',
              textAlign: TextAlign.center,
              style: _cairo(13,
                  color: hasError ? Colors.red[400] : Colors.grey[600],
                  height: 1.6),
            ),
            const SizedBox(height: 28),
            if (hasError)
              FilledButton.icon(
                onPressed: () =>
                    context.read<AiControllerCubit>().initConversation(),
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: const Text('إعادة المحاولة'),
                style: FilledButton.styleFrom(
                  backgroundColor: _kMintBrand,
                  padding:
                      const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16)),
                ),
              )
            else
              Wrap(
                spacing: 8,
                runSpacing: 8,
                alignment: WrapAlignment.center,
                children: [
                  _SuggestionChip('ماذا عندكم؟'),
                  _SuggestionChip('أريد منظفاً قوياً'),
                  _SuggestionChip('اقترح لي هدية'),
                  _SuggestionChip('ما هو أفضل منتج؟'),
                ],
              ),
          ],
        ),
      ),
    );
  }
}

class _SuggestionChip extends StatelessWidget {
  final String label;
  const _SuggestionChip(this.label);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () =>
          context.read<AiControllerCubit>().sendTextMessage(label),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: _kMintPaleBg,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: _kMintBrand.withValues(alpha: 0.3)),
        ),
        child: Text(
          label,
          style: _cairo(13, fontWeight: FontWeight.w600, color: _kMintDark),
        ),
      ),
    );
  }
}

// ── نص الكلام المؤقت ──────────────────────────────────────────────────────────
class _PartialSpeechBubble extends StatelessWidget {
  final String text;
  const _PartialSpeechBubble({required this.text});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(start: 12, end: 52, top: 4, bottom: 4),
      child: Align(
        alignment: AlignmentDirectional.centerStart,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
          decoration: BoxDecoration(
            color: _kMintBrand.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: _kMintBrand.withValues(alpha: 0.4),
              width: 1.5,
            ),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.mic_rounded, color: _kMintBrand, size: 14),
              const SizedBox(width: 6),
              Flexible(
                child: Text(
                  text,
                  style: _cairo(14,
                      color: _kMintDark, fontStyle: FontStyle.italic),
                  textDirection: TextDirection.rtl,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── مؤشر تفكير Gemini ─────────────────────────────────────────────────────────
// استبدل بـ AssistantTypingBubble من typing_indicator.dart
class _ThinkingIndicator extends StatelessWidget {
  const _ThinkingIndicator();

  @override
  Widget build(BuildContext context) => const AssistantTypingBubble();
}

// ── شريط الأدوات السفلي ───────────────────────────────────────────────────────
class _BottomBar extends StatelessWidget {
  final bool useHeroMic;
  final AiProcessingStatus status;
  final bool showTextField;
  final TextEditingController textController;
  final VoidCallback onMicTap;
  final VoidCallback onTextSend;
  final VoidCallback onToggleInput;

  const _BottomBar({
    this.useHeroMic = false,
    required this.status,
    required this.showTextField,
    required this.textController,
    required this.onMicTap,
    required this.onTextSend,
    required this.onToggleInput,
  });

  @override
  Widget build(BuildContext context) {
    Widget mic = VoiceMicButton(
      status: status,
      onTap: onMicTap,
    );
    if (useHeroMic) {
      mic = Hero(
        tag: 'ai_button',
        child: Material(
          type: MaterialType.transparency,
          child: mic,
        ),
      );
    }
    // رفع بصريّ يقارب وضع الزر فوق الشريط في الصفحة الرئيسية
    mic = Transform.translate(
      offset: const Offset(0, -12),
      child: mic,
    );

    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).padding.bottom + 12,
        top: 12,
        right: 16,
        left: 16,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(28),
          topRight: Radius.circular(28),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // مؤشر السحب
          Container(
            width: 36,
            height: 4,
            margin: const EdgeInsets.only(bottom: 16),
            decoration: BoxDecoration(
              color: Colors.grey[200],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              // زر التبديل بين الصوت والنص
              _InputToggleButton(
                showTextField: showTextField,
                onTap: onToggleInput,
              ),

              SizedBox(
                width: 96,
                height: 96,
                child: Center(child: mic),
              ),

              // زر المزايا الإضافية
              _ActionButton(
                icon: Icons.emoji_objects_outlined,
                onTap: () => context
                    .read<AiControllerCubit>()
                    .sendTextMessage('اقترح لي منتجات مميزة اليوم'),
              ),
            ],
          ),
          // حقل النص (يظهر/يختفي)
          AnimatedSize(
            duration: const Duration(milliseconds: 250),
            curve: Curves.easeOut,
            child: showTextField
                ? _TextField(
                    controller: textController,
                    onSend: onTextSend,
                  )
                : const SizedBox.shrink(),
          ),
        ],
      ),
    );
  }
}

class _InputToggleButton extends StatelessWidget {
  final bool showTextField;
  final VoidCallback onTap;
  const _InputToggleButton({required this.showTextField, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: showTextField
              ? _kMintBrand.withValues(alpha: 0.1)
              : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: _kMintBrand.withValues(alpha: 0.55),
            width: 1.2,
          ),
        ),
        child: Icon(
          showTextField ? Icons.keyboard_hide_rounded : Icons.keyboard_alt_outlined,
          color: _kMintDark,
          size: 20,
        ),
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  const _ActionButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: _kMintBrand.withValues(alpha: 0.55),
            width: 1.2,
          ),
        ),
        child: Icon(icon, color: _kMintDark, size: 20),
      ),
    );
  }
}

class _TextField extends StatelessWidget {
  final TextEditingController controller;
  final VoidCallback onSend;
  const _TextField({required this.controller, required this.onSend});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 12),
      child: Row(
        children: [
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: _kMintPaleBg,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: _kMintBrand.withValues(alpha: 0.2),
                ),
              ),
              child: TextField(
                controller: controller,
                style: _cairo(14, color: _kMintDark),
                textDirection: TextDirection.rtl,
                textInputAction: TextInputAction.send,
                onSubmitted: (_) => onSend(),
                decoration: InputDecoration(
                  hintText: 'اكتب رسالتك...',
                  hintTextDirection: TextDirection.rtl,
                  hintStyle: _cairo(14, color: Colors.grey[400]),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 12),
                  suffixIcon: IconButton(
                    icon: Icon(Icons.send_rounded,
                        color: _kMintBrand, size: 20),
                    onPressed: onSend,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── ورقة السلة السفلية ────────────────────────────────────────────────────────
class _CartSheet extends StatelessWidget {
  final BuildContext parentContext;

  const _CartSheet({required this.parentContext});

  /// فتح الورقة مع توريث CartCubit الحالي
  static void show(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder: (_) => BlocProvider.value(
        value: context.read<CartCubit>(),
        child: _CartSheet(parentContext: context),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: DraggableScrollableSheet(
        initialChildSize: 0.55,
        minChildSize: 0.35,
        maxChildSize: 0.92,
        expand: false,
        builder: (_, scrollCtrl) => BlocBuilder<CartCubit, CartState>(
          builder: (context, cart) {
            return Column(
              children: [
                // ── مقبض السحب ────────────────────────────────────────
                Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.symmetric(vertical: 14),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),

                // ── عنوان + زر المسح ──────────────────────────────────
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'السلة (${cart.distinctCount})',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: _kMintDark,
                        ),
                      ),
                      if (cart.isNotEmpty)
                        TextButton.icon(
                          onPressed: () =>
                              context.read<CartCubit>().clearCart(),
                          icon: const Icon(Icons.delete_outline_rounded,
                              size: 18, color: Color(0xFFFF4757)),
                          label: const Text('مسح الكل',
                              style: TextStyle(color: Color(0xFFFF4757))),
                        ),
                    ],
                  ),
                ),

                const Divider(height: 1),

                // ── قائمة المنتجات أو حالة الفراغ ──────────────────
                Expanded(
                  child: cart.isEmpty
                      ? _EmptyCart()
                      : ListView.separated(
                          controller: scrollCtrl,
                          padding: const EdgeInsets.all(16),
                          itemCount: cart.items.length,
                          separatorBuilder: (context, index) =>
                              const Divider(height: 1),
                          itemBuilder: (_, i) =>
                              _CartItemTile(item: cart.items[i]),
                        ),
                ),

                // ── الإجمالي وزر الشراء ───────────────────────────────
                if (cart.isNotEmpty)
                  _CartFooter(
                    total: cart.total,
                    parentContext: parentContext,
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

// ── عنصر في قائمة السلة ───────────────────────────────────────────────────────
class _CartItemTile extends StatelessWidget {
  final CartItem item;
  const _CartItemTile({required this.item});

  @override
  Widget build(BuildContext context) {
    final product = item.product;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: AppNetworkImage(
              product.imageUrl,
              width: 64,
              height: 64,
              fit: BoxFit.cover,
              error: Container(
                width: 64,
                height: 64,
                color: _kMintPaleBg,
                child: const Icon(Icons.image_not_supported_outlined,
                    color: _kMintBrand, size: 24),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  product.name,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: _kMintDark,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  '${product.effectivePrice.toStringAsFixed(0)} \u{20C1}',
                  style: const TextStyle(
                    fontSize: 13,
                    color: _kMintBrand,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
          _QuantityControls(item: item),
        ],
      ),
    );
  }
}

class _QuantityControls extends StatelessWidget {
  final CartItem item;
  const _QuantityControls({required this.item});

  @override
  Widget build(BuildContext context) {
    final cubit = context.read<CartCubit>();
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        _QtyButton(
          icon: Icons.remove,
          onTap: () =>
              cubit.updateQuantity(item.product.id, item.quantity - 1),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10),
          child: Text(
            '${item.quantity}',
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: _kMintDark,
            ),
          ),
        ),
        _QtyButton(
          icon: Icons.add,
          onTap: () =>
              cubit.updateQuantity(item.product.id, item.quantity + 1),
        ),
      ],
    );
  }
}

class _QtyButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  const _QtyButton({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 30,
        height: 30,
        decoration: BoxDecoration(
          color: _kMintPaleBg,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, size: 16, color: _kMintBrand),
      ),
    );
  }
}

// ── تذييل السلة ───────────────────────────────────────────────────────────────
class _CartFooter extends StatelessWidget {
  final double total;
  final BuildContext parentContext;

  const _CartFooter({
    required this.total,
    required this.parentContext,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
          20, 16, 20, MediaQuery.of(context).padding.bottom + 20),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFEEEEEE))),
      ),
      child: Row(
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('الإجمالي',
                  style: TextStyle(fontSize: 12, color: Colors.grey)),
              Text(
                '${total.toStringAsFixed(0)} \u{20C1}',
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: _kMintBrand,
                ),
              ),
            ],
          ),
          const SizedBox(width: 16),
          Expanded(
              child: ElevatedButton(
              onPressed: () async {
                Navigator.pop(context);
                await _openCheckoutSmart(parentContext);
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: _kMintBrand,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16)),
                elevation: 0,
              ),
              child: const Text(
                'إتمام الشراء',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── حالة السلة الفارغة ────────────────────────────────────────────────────────
class _EmptyCart extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('🛒', style: TextStyle(fontSize: 52)),
          const SizedBox(height: 12),
          const Text(
            'السلة فارغة',
            style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: _kMintDark),
          ),
          const SizedBox(height: 6),
          Text(
            'تحدث مع المساعد ليقترح عليك منتجات',
            style: TextStyle(fontSize: 13, color: Colors.grey[500]),
          ),
        ],
      ),
    );
  }
}
