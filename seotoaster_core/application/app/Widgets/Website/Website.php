<?php
/**
 * Description of Website
 *
 * @author iamne
 */
class Widgets_Website_Website extends Widgets_Abstract {

	const OPT_URL = 'url';
	const OPT_LANG = 'localization';
	const OPT_LANG_DROPDOWN = 'dropdown';

	protected function  _load() {
		$content = '';
		$type    = $this->_options[0];
		switch ($type) {
			case self::OPT_URL:
                $lang = isset($_COOKIE["localization"]) ? $_COOKIE["localization"] . '/' : '';
				$content = $this->_toasterOptions['websiteUrl'] . $lang;
			break;
			case self::OPT_LANG:
                $localization = Tools_Localization_Tools::getActiveLanguagesList();
                $langDefault  = Tools_Localization_Tools::getLangDefault();
                if(empty($localization)){
                    break;
                }

                $urls = Application_Model_Mappers_PageMapper::getInstance()
                    ->getCurrentPageLocalData($this->_toasterOptions['defaultLangId']);
                if (sizeof($urls) <= 1) {
                    return '';
                }

                $localizationContent = '';
                if(isset($this->_options[1]) && $this->_options[1] === self::OPT_LANG_DROPDOWN){
                    $localizationContent .= '<select id="website-localization" onchange="window.location.href = this.value;">';
                    foreach ($localization as $code => $name) {
                        $langCode = Zend_Locale::getLocaleToTerritory($code);
                        if (null !== $langCode && isset($urls[$langCode]['url'])) {
                            $lang = ($code === $langDefault) ? '' : $code . '/';
                            $selected = (($code === $langDefault) || (isset($_COOKIE["localization"]) && $code === $_COOKIE["localization"]) ? 'selected' : '');
                            $localizationContent .= '<option value="' . $this->_toasterOptions['websiteUrl'] . $lang . $urls[$langCode]['url'] . '"' . $selected . '>' . $name . '</option> ';
                        }
                    }
                    $localizationContent .= '</select>';

                } else {
                    if (isset($urls[$this->_toasterOptions['lang']]['url'])
                        && $urls[$this->_toasterOptions['lang']]['url'] === $this->_toasterOptions['url']
                    ) {
                        unset($urls[$this->_toasterOptions['lang']]);
                    }
                    else {
                        unset($urls[Zend_Locale::getLocaleToTerritory($langDefault)]);
                    }
                    foreach ($localization as $code => $name) {
                        $langCode = Zend_Locale::getLocaleToTerritory($code);
                        if (null !== $langCode && isset($urls[$langCode]['url'])) {
                            $lang = ($code === Tools_Localization_Tools::getLangDefault()) ? '' : $code . '/';
                            $localizationContent .= '<a href="' . $this->_toasterOptions['websiteUrl'] . $lang . $urls[$langCode]['url'] . '" title="' . $name . '">' . '<img class="lang-link" src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/flags/' . $code . '.png" alt="' . $name . '" border="0">' . '</a> ';
                        }
                    }
                }
                $content = $localizationContent;
			break;
		}
		return $content;
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Website url'),
				'option' => 'website:url'
			)
		);
	}

}

