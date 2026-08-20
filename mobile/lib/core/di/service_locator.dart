import '../../features/ai_assistant/data/services/voice_service.dart';
import '../../features/ai_assistant/data/datasources/gemini_remote_data_source.dart';
import '../../features/ai_assistant/data/repositories/ai_repository_impl.dart';
import '../../features/ai_assistant/data/services/ai_chat_api.dart';
import '../../features/ai_assistant/data/services/ai_service.dart';
import '../../features/ai_assistant/domain/usecases/send_message.dart';
import '../../features/ai_assistant/presentation/bloc/ai_assistant_bloc.dart';
import '../../features/ai_assistant/presentation/cubit/ai_controller_cubit.dart';
import '../../features/shop/data/datasources/shop_remote_data_source.dart';
import '../../features/shop/data/repositories/shop_repository_impl.dart';
import '../../features/shop/data/services/catalog_api.dart';
import '../../features/shop/domain/usecases/get_categories.dart';
import '../../features/shop/domain/usecases/get_products.dart';
import '../../features/shop/domain/usecases/search_products.dart';
import '../../features/shop/presentation/bloc/shop_bloc.dart';
import '../../features/shop/presentation/manager/cart_cubit.dart';
import '../network/network_info.dart';

class ServiceLocator {
  ServiceLocator._();

  static late ServiceLocator _instance;
  static ServiceLocator get instance => _instance;

  static void init({String geminiApiKey = ''}) {
    _instance = ServiceLocator._();
    _instance._geminiApiKey = geminiApiKey;
    _instance._setup();
  }

  late String _geminiApiKey;
  late final AiChatApi aiChatApi;
  late final AiService aiService;
  late final VoiceService voiceService;
  late final ShopRemoteDataSource shopDataSource;
  late final GeminiRemoteDataSource geminiDataSource;
  late final ShopRepositoryImpl shopRepository;
  late final AiRepositoryImpl aiRepository;
  late final GetProducts getProducts;
  late final GetCategories getCategories;
  late final SearchProducts searchProducts;
  late final SendMessage sendMessage;

  ShopBloc createShopBloc() => ShopBloc(
        getProducts: getProducts,
        getCategories: getCategories,
        searchProducts: searchProducts,
      );

  CartCubit createCartCubit() => CartCubit();

  AiControllerCubit createAiController({required CartCubit cartCubit}) =>
      AiControllerCubit(
        aiChatApi: aiChatApi,
        voiceService: voiceService,
        cartCubit: cartCubit,
      );

  AiAssistantBloc createAiAssistantBloc() =>
      AiAssistantBloc(sendMessage: sendMessage);

  void _setup() {
    final networkInfo = NetworkInfoImpl();
    final catalog = CatalogApi.instance;
    voiceService = VoiceService();
    aiChatApi = AiChatApi.instance;

    aiService = AiService(
      apiKey: _geminiApiKey,
      onGetProducts: ({String? categoryId, String? searchQuery}) {
        return catalog.products(categoryId: categoryId, q: searchQuery);
      },
      onGetProductDetail: catalog.product,
    );

    shopDataSource = ShopRemoteDataSourceImpl(api: catalog);
    geminiDataSource = GeminiRemoteDataSourceImpl(aiService: aiService);

    shopRepository = ShopRepositoryImpl(
      remoteDataSource: shopDataSource,
      networkInfo: networkInfo,
    );
    aiRepository = AiRepositoryImpl(
      remoteDataSource: geminiDataSource,
      networkInfo: networkInfo,
    );

    getProducts = GetProducts(shopRepository);
    getCategories = GetCategories(shopRepository);
    searchProducts = SearchProducts(shopRepository);
    sendMessage = SendMessage(aiRepository);
  }
}
