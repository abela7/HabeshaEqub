<?php
/**
 * HabeshaEqub Translation System
 * Simple JSON-based translation with caching
 */

class Translator {
    private static $instance = null;
    private $currentLanguage = 'en';
    private $translations = [];
    private $fallbackLanguage = 'en';
    
    private function __construct() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get language from session or set default
        $this->currentLanguage = $_SESSION['app_language'] ?? 'en';
        $this->loadTranslations();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load translations for current language
     */
    private function loadTranslations() {
        $langFile = __DIR__ . '/' . $this->currentLanguage . '.json';
        
        if (file_exists($langFile)) {
            $content = file_get_contents($langFile);
            $this->translations = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Translation JSON error for {$this->currentLanguage}: " . json_last_error_msg());
                $this->loadFallback();
            }
        } else {
            error_log("Translation file not found: {$langFile}");
            $this->loadFallback();
        }
    }
    
    /**
     * Load fallback language (English)
     */
    private function loadFallback() {
        if ($this->currentLanguage !== $this->fallbackLanguage) {
            $fallbackFile = __DIR__ . '/' . $this->fallbackLanguage . '.json';
            if (file_exists($fallbackFile)) {
                $content = file_get_contents($fallbackFile);
                $this->translations = json_decode($content, true);
            }
        }
    }
    
    /**
     * Get translation by key (dot notation supported)
     * Example: t('rules.page_title') or t('common.save')
     */
    public function translate($key, $params = []) {
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Return key if translation not found
                error_log("Translation key not found: {$key}");
                return $key;
            }
        }
        
        // Replace parameters if provided
        if (!empty($params) && is_string($value)) {
            foreach ($params as $param => $replacement) {
                $value = str_replace('{' . $param . '}', $replacement, $value);
            }
        }
        
        return $value;
    }
    
    /**
     * Set current language
     */
    public function setLanguage($language) {
        if (in_array($language, ['en', 'am'])) {
            $this->currentLanguage = $language;
            $_SESSION['app_language'] = $language;
            $this->loadTranslations();
            return true;
        }
        return false;
    }
    
    /**
     * Get current language
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    /**
     * Get available languages
     */
    public function getAvailableLanguages() {
        return [
            'en' => 'English',
            'am' => 'አማርኛ'
        ];
    }
    
    /**
     * Check if current language is RTL
     */
    public function isRTL() {
        return false; // Amharic is LTR, but you can modify this if needed
    }
}

/**
 * Global translation function - shorthand for Translator::translate()
 */
function t($key, $params = []) {
    return Translator::getInstance()->translate($key, $params);
}

/**
 * Get current language
 */
function getCurrentLanguage() {
    return Translator::getInstance()->getCurrentLanguage();
}

/**
 * Set language
 */
function setLanguage($language) {
    return Translator::getInstance()->setLanguage($language);
}

/**
 * Get available languages
 */
function getAvailableLanguages() {
    return Translator::getInstance()->getAvailableLanguages();
}
?> 