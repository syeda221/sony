<?php

namespace App\Services;

use App\Models\EcommerceOrder;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Sanitize phone number to international WhatsApp format (e.g. 923001234567)
     */
    public static function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return null;
        }

        $defaultCountryCode = '92';
        try {
            $defaultCountryCode = Setting::get('whatsapp_country_code', '92');
        } catch (\Throwable $e) {
            $defaultCountryCode = '92';
        }
        $defaultCountryCode = preg_replace('/[^0-9]/', '', (string)$defaultCountryCode) ?: '92';

        // If number starts with 00, remove leading 00
        if (str_starts_with($clean, '00')) {
            $clean = substr($clean, 2);
        }
        // If number starts with a single 0 (e.g. 03001234567), replace leading 0 with country code
        elseif (str_starts_with($clean, '0')) {
            $clean = $defaultCountryCode . substr($clean, 1);
        }
        // If number does not start with country code and length is typical local mobile length (e.g. 10 digits like 3001234567)
        elseif (!str_starts_with($clean, $defaultCountryCode) && strlen($clean) <= 10) {
            $clean = $defaultCountryCode . $clean;
        }

        return $clean;
    }

    /**
     * Replace template placeholders with real data
     */
    public static function renderTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $val) {
            $template = str_replace('{' . $key . '}', (string) $val, $template);
        }
        return $template;
    }

    /**
     * Get configured Sale Invoice message
     */
    public static function getSaleInvoiceMessage(array $data): string
    {
        $default = "Dear {customer_name}, your Invoice #{invoice_no} dated {date} has been prepared. Total Bill: Rs. {total_amount}, Received: Rs. {paid_amount}, Remaining Balance: Rs. {remaining_balance}. Thank you! - {company_name}";
        try {
            $template = Setting::get('whatsapp_sale_template', $default) ?: $default;
        } catch (\Throwable $e) {
            $template = $default;
        }
        return self::renderTemplate($template, $data);
    }

    /**
     * Get configured Purchase Invoice message
     */
    public static function getPurchaseInvoiceMessage(array $data): string
    {
        $default = "Dear {vendor_name}, Purchase Bill #{invoice_no} dated {date} has been recorded. Total Bill: Rs. {total_amount}, Paid: Rs. {paid_amount}, Remaining Balance: Rs. {remaining_balance}. Thank you! - {company_name}";
        try {
            $template = Setting::get('whatsapp_purchase_template', $default) ?: $default;
        } catch (\Throwable $e) {
            $template = $default;
        }
        return self::renderTemplate($template, $data);
    }

    /**
     * Get configured Receipt Voucher message
     */
    public static function getReceiptVoucherMessage(array $data): string
    {
        $default = "Dear {customer_name}, we have received payment of Rs. {paid_amount} against Voucher #{voucher_no} on {date}. Your remaining balance is Rs. {remaining_balance}. Thank you! - {company_name}";
        try {
            $template = Setting::get('whatsapp_receipt_template', $default) ?: $default;
        } catch (\Throwable $e) {
            $template = $default;
        }
        return self::renderTemplate($template, $data);
    }

    /**
     * Get configured Payment Voucher message
     */
    public static function getPaymentVoucherMessage(array $data): string
    {
        $default = "Dear {vendor_name}, payment of Rs. {paid_amount} has been made under Voucher #{voucher_no} on {date}. Your remaining ledger balance is Rs. {remaining_balance}. Thank you! - {company_name}";
        try {
            $template = Setting::get('whatsapp_payment_template', $default) ?: $default;
        } catch (\Throwable $e) {
            $template = $default;
        }
        return self::renderTemplate($template, $data);
    }

    /**
     * Generate WhatsApp Web URL
     */
    public static function getWhatsAppUrl(?string $phone, string $message): ?string
    {
        $cleanPhone = self::formatPhoneNumber($phone);
        if (empty($cleanPhone)) {
            return null;
        }

        return 'https://web.whatsapp.com/send?phone=' . $cleanPhone . '&text=' . rawurlencode($message);
    }

    /**
     * Generate WhatsApp Desktop App Protocol URL (whatsapp://send?phone=...&text=...)
     */
    public static function getWhatsAppAppUrl(?string $phone, string $message): ?string
    {
        $cleanPhone = self::formatPhoneNumber($phone);
        if (empty($cleanPhone)) {
            return null;
        }

        return 'whatsapp://send?phone=' . $cleanPhone . '&text=' . rawurlencode($message);
    }

    /**
     * Send WhatsApp notification based on order status change (Ecommerce).
     */
    public function sendStatusNotification(EcommerceOrder $order, string $newStatus)
    {
        try {
            $brandName = Setting::where('key', 'web_site_name')->value('value') ?: 'TrendHub';
            $customerName = $order->customer->name ?? $order->shipping_name;
            $orderId = $order->order_number;
            $orderTotal = number_format($order->total, 2);
            $websiteUrl = url('/');
            $phone = self::formatPhoneNumber($order->shipping_phone);

            // Fetch Support Phone dynamically
            $supportPhoneSetting = Setting::where('key', 'web_whatsapp_number')->value('value') 
                ?: Setting::where('key', 'web_contact_phone')->value('value');
                
            $supportUrlLine = '';
            if (!empty($supportPhoneSetting)) {
                $supportPhone = self::formatPhoneNumber($supportPhoneSetting);
                if ($supportPhone) {
                    $supportUrlLine = "\nIf you need any assistance, feel free to contact our customer support:\nhttps://wa.me/" . $supportPhone;
                }
            }

            if (!$phone) {
                Log::warning("WhatsApp notification skipped: Invalid phone number for Order #{$orderId}");
                return false;
            }

            $message = '';

            switch ($newStatus) {
                case 'processing':
                    $message = "Order Confirmed!\n" .
                               "Dear {$customerName},\n" .
                               "Your order {$orderId} from {$brandName} has been successfully confirmed and verified.\n" .
                               "It will be delivered to you within 3-4 days.{$supportUrlLine}\n" .
                               "Thank you for shopping with us.";
                    break;

                case 'shipped':
                    $courierName = $order->courier_name ?: 'Courier Service';
                    $trackingInfo = '';
                    if ($order->tracking_url) {
                        $trackingInfo = "\nTracking URL: " . $order->tracking_url;
                    } elseif ($order->tracking_number) {
                        $trackingInfo = "\nTracking Number: " . $order->tracking_number;
                    }

                    $message = "Order Dispatched\n" .
                               "Dear {$customerName},\n" .
                               "Your order from {$brandName} has been shipped.\n" .
                               "Order ID: {$orderId}\n" .
                               "Amount: PKR {$orderTotal}\n\n" .
                               "Courier: {$courierName}" .
                               "{$trackingInfo}\n\n" .
                               "Thank you for shopping with us.\n" .
                               "Visit Website:\n" .
                               "{$websiteUrl}";
                    break;

                default:
                    return false;
            }

            return $this->sendTextMessage($phone, $message);

        } catch (\Exception $e) {
            Log::error("WhatsApp service notification error for Order #{$order->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send HTTP request to WhatsApp API.
     */
    protected function sendTextMessage(string $phone, string $message)
    {
        $apiUrl = env('WHATSAPP_API_URL');
        $apiKey = env('WHATSAPP_API_KEY');

        Log::info("Sending WhatsApp to {$phone}. Message:\n{$message}");

        if (empty($apiUrl)) {
            Log::info("WhatsApp API URL not set in .env. Message logged to laravel.log instead.");
            return true;
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json'
            ])->post($apiUrl, [
                'to' => $phone,
                'message' => $message
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("WhatsApp API returned error code {$response->status()}: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WhatsApp API connection failed: " . $e->getMessage());
            return false;
        }
    }
}
