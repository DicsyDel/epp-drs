<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    Locale
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	/**
     * @name Locale
     * @category   LibWebta
     * @package    Locale
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	class Locale extends Core
	{
		
		/**
		* Get Locale name and return Language name for this locale
		* @access public
		* @param string $locale_name
		* @static
		* @return string localename
		*/
		public static function GetLanguageNameFromLocale($locale_name)
		{
			$langs = array(
                    'af_ZA' => 'Afrikaans',
                    'sq_AL' => 'Albanian',
                    'ar_SA' => 'Arabic - Saudi Arabia',
                    'ar_IQ' => 'Arabic - Iraq',
                    'ar_EG' => 'Arabic - Egypt',
                    'ar_LY' => 'Arabic - Libya',
                    'ar_DZ' => 'Arabic - Algeria',
                    'ar_MA' => 'Arabic - Morocco',
                    'ar_TN' => 'Arabic - Tunisia',
                    'ar_OM' => 'Arabic - Oman',
                    'ar_YE' => 'Arabic - Yemen',
                    'ar_SY' => 'Arabic - Syria',
                    'ar_JO' => 'Arabic - Jordan',
                    'ar_LB' => 'Arabic - Lebanon',
                    'ar_KW' => 'Arabic - Kuwait',
                    'ar_AE' => 'Arabic - United Arab Emirates',
                    'ar_BH' => 'Arabic - Bahrain',
                    'ar_QA' => 'Arabic - Qatar',
                    'hy_AM' => 'Armenian',
                    'az_AZ' => 'Azeri Latin',
                    'az_AZ' => 'Azeri - Cyrillic',
                    'eu_ES' => 'Basque',
                    'be_BY' => 'Belarusian',
                    'bn_IN' => 'Begali',
                    'bs_BA' => 'Bosnian',
                    'bs_BA' => 'Bosnian - Cyrillic',
                    'br_FR' => 'Breton - France',
                    'bg_BG' => 'Bulgarian',
                    'ca_ES' => 'Catalan',
                    'zh_TW' => 'Chinese - Taiwan',
                    'zh_CN' => 'Chinese - PRC',
                    'zh_HK' => 'Chinese - Hong Kong S.A.R.',
                    'zh_SG' => 'Chinese - Singapore',
                    'zh_MO' => 'Chinese - Macao S.A.R.',
                    'hr_HR' => 'Croatian',
                    'hr_BA' => 'Croatian - Bosnia',
                    'cs_CZ' => 'Czech',
                    'da_DK' => 'Danish',
                    'nl_NL' => 'Dutch - The Netherlands',
                    'nl_BE' => 'Dutch - Belgium',
                    'en_US' => 'English - United States',
                    'en_GB' => 'English - United Kingdom',
                    'en_AU' => 'English - Australia',
                    'en_CA' => 'English - Canada',
                    'en_NZ' => 'English - New Zealand',
                    'en_IE' => 'English - Ireland',
                    'en_ZA' => 'English - South Africa',
                    'en_JA' => 'English - Jamaica',
                    'en_CB' => 'English - Carribbean',
                    'en_BZ' => 'English - Belize',
                    'en_TT' => 'English - Trinidad',
                    'en_ZW' => 'English - Zimbabwe',
                    'en_PH' => 'English - Phillippines',
                    'et_EE' => 'Estonian',
                    'fo_FO' => 'Faroese',
                    'fi_FI' => 'Finnish',
                    'fr_FR' => 'French - France',
                    'fr_BE' => 'French - Belgium',
                    'fr_CA' => 'French - Canada',
                    'fr_CH' => 'French - Switzerland',
                    'fr_LU' => 'French - Luxembourg',
                    'fr_MC' => 'French - Monaco',
                    'fy_NL' => 'Frisian - Netherlands',
                    'gl_ES' => 'Galician',
                    'ka_GE' => 'Georgian',
                    'de_DE' => 'German - Germany',
                    'de_CH' => 'German - Switzerland',
                    'de_AT' => 'German - Austria',
                    'de_LU' => 'German - Luxembourg',
                    'de_LI' => 'German - Liechtenstein',
                    'el_GR' => 'Greek',
                    'gu_IN' => 'Gujarati',
                    'he_IL' => 'Hebrew',
                    'hi_IN' => 'Hindi',
                    'hu_HU' => 'Hungarian',
                    'is_IS' => 'Icelandic',
                    'id_ID' => 'Indonesian',
                    'iu_CA' => 'Inuktitut',
                    'iu_CA' => 'Inuktitut - Latin',
                    'ga_IE' => 'Irish - Ireland',
                    'xh_ZA' => 'Xhosa - South Africa',
                    'zu_ZA' => 'Zulu',
                    'it_IT' => 'Italian - Italy',
                    'it_CH' => 'Italian - Switzerland',
                    'ja_JP' => 'Japanese',
                    'kn_IN' => 'Kannada - India',
                    'kk_KZ' => 'Kazakh',
                    'ko_KR' => 'Korean',
                    'ky_KG' => 'Kyrgyz',
                    'lv_LV' => 'Latvian',
                    'lt_LT' => 'Lithuanian',
                    'lb_LU' => 'Luxembourgish',
                    'mk_MK' => 'FYRO Macedonian',
                    'ms_MY' => 'Malay - Malaysia',
                    'ms_BN' => 'Malay - Brunei',
                    'ml_IN' => 'Malayalam - India',
                    'mt_MT' => 'Maltese',
                    'mi_NZ' => 'Maori',
                    'mr_IN' => 'Marathi',
                    'mn_MN' => 'Mongolian',
                    'ne_NP' => 'Nepali',
                    'nb_NO' => 'Norwegian - Bokmal',
                    'nn_NO' => 'Norwegian - Nynorsk',
                    'oc_FR' => 'Occitan - France',
                    'or_IN' => 'Oriya - India',
                    'ps_AF' => 'Pashto - Afghanistan',
                    'fa_IR' => 'Persian',
                    'pl_PL' => 'Polish',
                    'pt_BR' => 'Portuguese - Brazil',
                    'pt_PT' => 'Portuguese - Portugal',
                    'pa_IN' => 'Punjabi',
                    'ro_RO' => 'Romanian - Romania',
                    'rm_CH' => 'Raeto-Romanese',
                    'ru_RU' => 'Russian',
                    'se_NO' => 'Sami Northern Norway',
                    'se_SE' => 'Sami Northern Sweden',
                    'se_FI' => 'Sami Northern Finland',
                    'sa_IN' => 'Sanskrit',
                    'sr_SP' => 'Serbian - Cyrillic',
                    'sr_BA' => 'Serbian - Bosnia Cyrillic',
                    'sr_SP' => 'Serbian - Latin',
                    'sr_BA' => 'Serbian - Bosnia Latin',
                    'ns_ZA' => 'Northern Sotho',
                    'tn_ZA' => 'Setswana - Southern Africa',
                    'sk_SK' => 'Slovak',
                    'sl_SI' => 'Slovenian',
                    'es_ES' => 'Spanish - Spain',
                    'es_MX' => 'Spanish - Mexico',
                    'es_ES' => 'Spanish - Spain (Modern)',
                    'es_GT' => 'Spanish - Guatemala',
                    'es_CR' => 'Spanish - Costa Rica',
                    'es_PA' => 'Spanish - Panama',
                    'es_DO' => 'Spanish - Dominican Republic',
                    'es_VE' => 'Spanish - Venezuela',
                    'es_CO' => 'Spanish - Colombia',
                    'es_PE' => 'Spanish - Peru',
                    'es_AR' => 'Spanish - Argentina',
                    'es_EC' => 'Spanish - Ecuador',
                    'es_CL' => 'Spanish - Chile',
                    'es_UR' => 'Spanish - Uruguay',
                    'es_PY' => 'Spanish - Paraguay',
                    'es_BO' => 'Spanish - Bolivia',
                    'es_SV' => 'Spanish - El Salvador',
                    'es_HN' => 'Spanish - Honduras',
                    'es_NI' => 'Spanish - Nicaragua',
                    'es_PR' => 'Spanish - Puerto Rico',
                    'sw_KE' => 'Swahili',
                    'sv_SE' => 'Swedish - Sweden',
                    'sv_FI' => 'Swedish - Finland',
                    'ta_IN' => 'Tamil',
                    'tt_RU' => 'Tatar',
                    'te_IN' => 'Telugu',
                    'th_TH' => 'Thai',
                    'tr_TR' => 'Turkish',
                    'uk_UA' => 'Ukrainian',
                    'ur_PK' => 'Urdu',
                    'ur_IN' => 'Urdu - India',
                    'uz_UZ' => 'Uzbek - Latin',
                    'uz_UZ' => 'Uzbek - Cyrillic',
                    'vi_VN' => 'Vietnamese',
                    'cy_GB' => 'Welsh'
                );
                
            return $langs[$locale_name];
		}
		
	}
	
?>
