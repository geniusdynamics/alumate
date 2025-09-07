<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstitutionalDomain implements ValidationRule
{
    /**
     * Known institutional domain patterns and suffixes
     */
    private array $institutionalPatterns = [
        // Educational domains
        '.edu',
        '.edu.au',
        '.edu.ca',
        '.edu.uk',
        '.ac.uk',
        '.ac.nz',
        '.ac.za',
        '.edu.sg',
        '.edu.my',
        '.edu.ph',
        '.edu.in',
        '.edu.pk',
        '.edu.bd',
        '.edu.lk',
        '.edu.np',
        '.edu.bt',
        '.edu.mv',
        '.edu.af',
        '.edu.ir',
        '.edu.iq',
        '.edu.jo',
        '.edu.kw',
        '.edu.lb',
        '.edu.om',
        '.edu.qa',
        '.edu.sa',
        '.edu.sy',
        '.edu.ae',
        '.edu.ye',
        '.edu.eg',
        '.edu.ly',
        '.edu.ma',
        '.edu.tn',
        '.edu.dz',
        '.edu.sd',
        '.edu.et',
        '.edu.ke',
        '.edu.tz',
        '.edu.ug',
        '.edu.rw',
        '.edu.bi',
        '.edu.mw',
        '.edu.zm',
        '.edu.zw',
        '.edu.bw',
        '.edu.sz',
        '.edu.ls',
        '.edu.na',
        '.edu.ao',
        '.edu.mz',
        '.edu.mg',
        '.edu.mu',
        '.edu.sc',
        '.edu.km',
        '.edu.dj',
        '.edu.so',
        '.edu.er',
        '.edu.cf',
        '.edu.td',
        '.edu.cm',
        '.edu.gq',
        '.edu.ga',
        '.edu.cg',
        '.edu.cd',
        '.edu.st',
        '.edu.gh',
        '.edu.tg',
        '.edu.bj',
        '.edu.bf',
        '.edu.ci',
        '.edu.lr',
        '.edu.sl',
        '.edu.gn',
        '.edu.gw',
        '.edu.cv',
        '.edu.sn',
        '.edu.gm',
        '.edu.ml',
        '.edu.mr',
        '.edu.ne',
        '.edu.ng',
        
        // Academic domains
        '.ac.',
        
        // University-specific patterns
        'university',
        'college',
        'school',
        'institute',
        'academy',
    ];

    /**
     * Common non-institutional domains to exclude
     */
    private array $excludedDomains = [
        'gmail.com',
        'yahoo.com',
        'hotmail.com',
        'outlook.com',
        'aol.com',
        'icloud.com',
        'protonmail.com',
        'tutanota.com',
        'yandex.com',
        'mail.ru',
        'qq.com',
        '163.com',
        '126.com',
        'sina.com',
        'sohu.com',
        'live.com',
        'msn.com',
        'comcast.net',
        'verizon.net',
        'att.net',
        'cox.net',
        'charter.net',
        'earthlink.net',
        'sbcglobal.net',
        'bellsouth.net',
        'rocketmail.com',
        'ymail.com',
        'mail.com',
        'gmx.com',
        'web.de',
        't-online.de',
        'freenet.de',
        'arcor.de',
        'alice.it',
        'libero.it',
        'virgilio.it',
        'tiscali.it',
        'orange.fr',
        'wanadoo.fr',
        'laposte.net',
        'free.fr',
        'sfr.fr',
        'neuf.fr',
        'alice.fr',
        'club-internet.fr',
    ];

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

        // Extract domain from email
        $emailParts = explode('@', $value);
        if (count($emailParts) !== 2) {
            $fail('The :attribute must be a valid email address.');
            return;
        }

        $domain = strtolower(trim($emailParts[1]));

        // Check if it's a known non-institutional domain
        if (in_array($domain, $this->excludedDomains)) {
            $fail('The :attribute must use an institutional email address. Personal email addresses (like Gmail, Yahoo, etc.) are not allowed.');
            return;
        }

        // Check for institutional patterns
        $isInstitutional = false;

        // Check for .edu and other educational domains
        foreach ($this->institutionalPatterns as $pattern) {
            if (str_contains($pattern, '.')) {
                // Domain suffix pattern
                if (str_ends_with($domain, $pattern)) {
                    $isInstitutional = true;
                    break;
                }
            } else {
                // Keyword pattern
                if (str_contains($domain, $pattern)) {
                    $isInstitutional = true;
                    break;
                }
            }
        }

        // Additional heuristics for institutional domains
        if (!$isInstitutional) {
            // Check for common institutional keywords in domain
            $institutionalKeywords = [
                'univ', 'college', 'school', 'institute', 'academy', 'campus',
                'education', 'learning', 'student', 'faculty', 'academic',
                'research', 'library', 'alumni', 'grad', 'undergrad'
            ];

            foreach ($institutionalKeywords as $keyword) {
                if (str_contains($domain, $keyword)) {
                    $isInstitutional = true;
                    break;
                }
            }
        }

        // Check domain structure (institutional domains often have specific patterns)
        if (!$isInstitutional) {
            // Many institutional domains have subdomain structure
            $domainParts = explode('.', $domain);
            if (count($domainParts) >= 3) {
                // Check if any part contains institutional keywords
                foreach ($domainParts as $part) {
                    foreach (['edu', 'ac', 'univ', 'college', 'school'] as $keyword) {
                        if (str_contains($part, $keyword)) {
                            $isInstitutional = true;
                            break 2;
                        }
                    }
                }
            }
        }

        // Final validation
        if (!$isInstitutional) {
            $fail('The :attribute must be from an educational institution. Please use your institutional email address (e.g., .edu domain or official school email).');
        }
    }
}
