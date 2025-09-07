<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow empty values, use 'required' rule separately if needed
        }

        // Remove all non-digit characters except +
        $cleanPhone = preg_replace('/[^\d+]/', '', $value);
        
        // Check if it's a valid international format
        if (preg_match('/^\+[1-9]\d{1,14}$/', $cleanPhone)) {
            return; // Valid international format
        }
        
        // Check if it's a valid US format (10-11 digits)
        if (preg_match('/^1?\d{10}$/', $cleanPhone)) {
            return; // Valid US format
        }
        
        // Enhanced international patterns with more countries
        $patterns = [
            '/^\+1[2-9]\d{2}[2-9]\d{2}\d{4}$/', // US/Canada: +1NXXNXXXXXX
            '/^\+44[1-9]\d{8,9}$/',             // UK: +44XXXXXXXXX
            '/^\+33[1-9]\d{8}$/',               // France: +33XXXXXXXXX
            '/^\+49[1-9]\d{10,11}$/',           // Germany: +49XXXXXXXXXXX
            '/^\+81[1-9]\d{8,9}$/',             // Japan: +81XXXXXXXXXX
            '/^\+86[1-9]\d{10}$/',              // China: +86XXXXXXXXXXX
            '/^\+91[6-9]\d{9}$/',               // India: +91XXXXXXXXXX
            '/^\+61[2-9]\d{8}$/',               // Australia: +61XXXXXXXXX
            '/^\+55[1-9]\d{10}$/',              // Brazil: +55XXXXXXXXXXX
            '/^\+7[3-9]\d{9}$/',                // Russia: +7XXXXXXXXXX
            '/^\+39[0-9]\d{8,9}$/',             // Italy: +39XXXXXXXXXX
            '/^\+34[6-9]\d{8}$/',               // Spain: +34XXXXXXXXX
            '/^\+31[6]\d{8}$/',                 // Netherlands: +31XXXXXXXXX
            '/^\+46[7]\d{8}$/',                 // Sweden: +46XXXXXXXXX
            '/^\+47[4-9]\d{7}$/',               // Norway: +47XXXXXXXX
            '/^\+45[2-9]\d{7}$/',               // Denmark: +45XXXXXXXX
            '/^\+358[4-5]\d{7,8}$/',            // Finland: +358XXXXXXXX
            '/^\+32[4-9]\d{8}$/',               // Belgium: +32XXXXXXXXX
            '/^\+41[7-8]\d{8}$/',               // Switzerland: +41XXXXXXXXX
            '/^\+43[6-9]\d{8,10}$/',            // Austria: +43XXXXXXXXXX
            '/^\+48[5-9]\d{8}$/',               // Poland: +48XXXXXXXXX
            '/^\+420[6-9]\d{8}$/',              // Czech Republic: +420XXXXXXXXX
            '/^\+36[2-9]\d{8}$/',               // Hungary: +36XXXXXXXXX
            '/^\+351[9]\d{8}$/',                // Portugal: +351XXXXXXXXX
            '/^\+30[6-9]\d{9}$/',               // Greece: +30XXXXXXXXXX
            '/^\+90[5]\d{9}$/',                 // Turkey: +90XXXXXXXXXX
            '/^\+972[5]\d{8}$/',                // Israel: +972XXXXXXXXX
            '/^\+971[5]\d{8}$/',                // UAE: +971XXXXXXXXX
            '/^\+966[5]\d{8}$/',                // Saudi Arabia: +966XXXXXXXXX
            '/^\+65[8-9]\d{7}$/',               // Singapore: +65XXXXXXXX
            '/^\+60[1]\d{8,9}$/',               // Malaysia: +60XXXXXXXXX
            '/^\+66[6-9]\d{8}$/',               // Thailand: +66XXXXXXXXX
            '/^\+84[3-9]\d{8}$/',               // Vietnam: +84XXXXXXXXX
            '/^\+63[9]\d{9}$/',                 // Philippines: +63XXXXXXXXXX
            '/^\+62[8]\d{8,10}$/',              // Indonesia: +62XXXXXXXXXX
            '/^\+82[1]\d{8,9}$/',               // South Korea: +82XXXXXXXXX
            '/^\+852[5-9]\d{7}$/',              // Hong Kong: +852XXXXXXXX
            '/^\+886[9]\d{8}$/',                // Taiwan: +886XXXXXXXXX
            '/^\+64[2]\d{7,9}$/',               // New Zealand: +64XXXXXXXXX
            '/^\+27[6-8]\d{8}$/',               // South Africa: +27XXXXXXXXX
            '/^\+234[7-9]\d{9}$/',              // Nigeria: +234XXXXXXXXXX
            '/^\+254[7]\d{8}$/',                // Kenya: +254XXXXXXXXX
            '/^\+20[1]\d{9}$/',                 // Egypt: +20XXXXXXXXXX
            '/^\+52[1]\d{10}$/',                // Mexico: +52XXXXXXXXXXX
            '/^\+54[9]\d{8,10}$/',              // Argentina: +54XXXXXXXXXX
            '/^\+56[9]\d{8}$/',                 // Chile: +56XXXXXXXXX
            '/^\+57[3]\d{9}$/',                 // Colombia: +57XXXXXXXXXX
            '/^\+51[9]\d{8}$/',                 // Peru: +51XXXXXXXXX
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                return; // Valid format found
            }
        }
        
        // Additional validation for minimum/maximum length
        $length = strlen($cleanPhone);
        if ($length < 7) {
            $fail('The :attribute is too short. Please enter a valid phone number.');
            return;
        }
        
        if ($length > 15) {
            $fail('The :attribute is too long. Please enter a valid phone number.');
            return;
        }
        
        // Check for obviously invalid patterns
        if (preg_match('/^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/', $cleanPhone)) {
            $fail('The :attribute cannot be all the same digit.');
            return;
        }
        
        if (preg_match('/^123456|^654321|^111111|^000000|^987654|^555555/', $cleanPhone)) {
            $fail('The :attribute appears to be a test or invalid number.');
            return;
        }
        
        // Check for sequential numbers (likely fake)
        if (preg_match('/^(012345|123456|234567|345678|456789|567890|098765|987654|876543|765432|654321|543210)/', $cleanPhone)) {
            $fail('The :attribute appears to contain sequential digits which are not valid.');
            return;
        }
        
        // Check for emergency numbers
        $emergencyNumbers = ['911', '999', '112', '000', '101', '102', '103', '108', '119'];
        foreach ($emergencyNumbers as $emergency) {
            if (str_contains($cleanPhone, $emergency)) {
                $fail('The :attribute cannot be an emergency number.');
                return;
            }
        }
        
        $fail('The :attribute must be a valid phone number. Please include country code for international numbers.');
    }
}
