<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Mada = 'mada';
    case ApplePay = 'apple_pay';
    case StcPay = 'stc_pay';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'الدفع عند الاستلام',
            self::Mada => 'مدى',
            self::Card => 'فيزا / ماستركارد',
            self::ApplePay => 'Apple Pay',
            self::StcPay, self::Wallet => 'STC Pay',
            self::BankTransfer => 'تحويل بنكي',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Cash => 'ادفع كاش لمندوب التوصيل في السعودية',
            self::Mada => 'بطاقة مدى السعودية — يُؤكد المتجر العملية',
            self::Card => 'ادفع ببطاقة فيزا أو ماستركارد — يُؤكد المتجر العملية',
            self::ApplePay => 'ادفع عبر Apple Pay — يُؤكد المتجر العملية',
            self::StcPay, self::Wallet => 'محفظة STC Pay — يُؤكد المتجر العملية',
            self::BankTransfer => 'حوّل على حساب المتجر ثم انتظر تأكيد الإدارة',
        };
    }

    /**
     * @return list<self>
     */
    public static function checkoutOptions(): array
    {
        return [
            self::Cash,
            self::Mada,
            self::ApplePay,
            self::StcPay,
            self::BankTransfer,
        ];
    }
}
