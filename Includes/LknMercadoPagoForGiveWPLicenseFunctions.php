<?php

namespace Lkn\LknMercadoPagoForGiveWp\Includes;

abstract class LknMercadoPagoForGiveWPLicenseFunctions {
    /**
     * @since      1.0.0
     *
     * @package    Lkn_Give_Getnet
     * @subpackage Lkn_Give_Getnet/includes
     */

    // Exit, if accessed directly.    

    /**
     * Validates the Lkn_Give_Getnet license key
     *
     * @param string $licensekey - license key provided
     * @param string $localkey - license key stored
     *
     * @return array
     */
    final public static function validate_license($licensekey, $localkey = '') {
        $whmcsurl = 'https://cliente.linknacional.com.br/';
        $licensing_secret_key = 'LinknacionalxVisaCheckout';
        $localkeydays = 90;
        $allowcheckfaildays = 5;

        $check_token = time() . md5(wp_rand(100000000, mt_getrandmax()) . $licensekey);
        $checkdate = gmdate('Ymd');
        $domain = $_SERVER['SERVER_NAME'];
        $usersip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
        $dirpath = __DIR__;
        $verifyfilepath = 'modules/servers/licensing/verify.php';
        $localkeyvalid = false;

        if ($localkey) {
            $localkey = str_replace("\n", '', $localkey); // Remove the line breaks
            $localdata = substr($localkey, 0, strlen($localkey) - 32); // Extract License Data
            $md5hash = substr($localkey, strlen($localkey) - 32); // Extract MD5 Hash
            if (md5($localdata . $licensing_secret_key) == $md5hash) {
                $localdata = strrev($localdata); // Reverse the string
                $md5hash = substr($localdata, 0, 32); // Extract MD5 Hash
                $localdata = substr($localdata, 32); // Extract License Data
                $localdata = base64_decode($localdata, true);
                $localkeyresults = json_decode($localdata, true);
                $originalcheckdate = $localkeyresults['checkdate'];
                if (md5($originalcheckdate . $licensing_secret_key) == $md5hash) {
                    $localexpiry = gmdate('Ymd', mktime(0, 0, 0, gmdate('m'), gmdate('d') - $localkeydays, gmdate('Y')));
                    if ($originalcheckdate > $localexpiry) {
                        $localkeyvalid = true;
                        $results = $localkeyresults;
                        // Validação de domínio desabilitada temporariamente
                        /* $validdomains = explode(',', $results['validdomain']);
                        if (!in_array($_SERVER['SERVER_NAME'], $validdomains)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = 'Invalid';
                            $results = [];
                        } */
                        $validips = explode(',', $results['validip']);
                        if ( ! in_array($usersip, $validips, true)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = 'Invalid';
                            $results = array();
                        }
                        // Validação de diretório desabilitada temporariamente
                        /* $validdirs = explode(',', $results['validdirectory']);
                        if (!in_array($dirpath, $validdirs)) {
                            $localkeyvalid = false;
                            $localkeyresults['status'] = 'Invalid';
                            $results = [];
                        } */
                    }
                }
            }
        }
        if ( ! $localkeyvalid) {
            $responseCode = 0;
            $postfields = array(
                'licensekey' => $licensekey,
                'domain' => $domain,
                'ip' => $usersip,
                'dir' => $dirpath,
            );
            if ($check_token) {
                $postfields['check_token'] = $check_token;
            }
            $query_string = '';
            foreach ($postfields as $k => $v) {
                $query_string .= $k . '=' . urlencode($v) . '&';
            }
            if (function_exists('curl_exec')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $whmcsurl . $verifyfilepath);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $data = curl_exec($ch);
                $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
            } else {
                $responseCodePattern = '/^HTTP\/\d+\.\d+\s+(\d+)/';
                $fp = @fsockopen($whmcsurl, 80, $errno, $errstr, 5);
                if ($fp) {
                    $newlinefeed = "\r\n";
                    $header = 'POST ' . $whmcsurl . $verifyfilepath . ' HTTP/1.0' . $newlinefeed;
                    $header .= 'Host: ' . $whmcsurl . $newlinefeed;
                    $header .= 'Content-type: application/x-www-form-urlencoded' . $newlinefeed;
                    $header .= 'Content-length: ' . @strlen($query_string) . $newlinefeed;
                    $header .= 'Connection: close' . $newlinefeed . $newlinefeed;
                    $header .= $query_string;
                    $data = $line = '';
                    @stream_set_timeout($fp, 20);
                    @fputs($fp, $header);
                    $status = @socket_get_status($fp);
                    while ( ! @feof($fp) && $status) {
                        $line = @fgets($fp, 1024);
                        $patternMatches = array();
                        if ( ! $responseCode
                            && preg_match($responseCodePattern, trim($line), $patternMatches)
                        ) {
                            $responseCode = (empty($patternMatches[1])) ? 0 : $patternMatches[1];
                        }
                        $data .= $line;
                        $status = @socket_get_status($fp);
                    }
                    @fclose($fp);
                }
            }
            if (200 != $responseCode) {
                $localexpiry = gmdate('Ymd', mktime(0, 0, 0, gmdate('m'), gmdate('d') - ($localkeydays + $allowcheckfaildays), gmdate('Y')));
                if ($originalcheckdate > $localexpiry) {
                    $results = $localkeyresults;
                } else {
                    $results = array();
                    $results['status'] = 'Invalid';
                    $results['description'] = 'Remote Check Failed';

                    return $results;
                }
            } else {
                preg_match_all('/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches);
                $results = array();
                foreach ($matches[1] as $k => $v) {
                    $results[$v] = $matches[2][$k];
                }
            }
            if ( ! is_array($results)) {
                die('Invalid License Server Response');
            }
            if (isset($results['md5hash'])) {
                if (md5($licensing_secret_key . $check_token) != $results['md5hash']) {
                    $results['status'] = 'Invalid';
                    $results['description'] = 'MD5 Checksum Verification Failed';

                    return $results;
                }
            }
            if ('Active' == $results['status']) {
                $results['checkdate'] = $checkdate;
                $data_encoded = wp_json_encode($results);
                $data_encoded = base64_encode($data_encoded);
                $data_encoded = md5($checkdate . $licensing_secret_key) . $data_encoded;
                $data_encoded = strrev($data_encoded);
                $data_encoded = $data_encoded . md5($data_encoded . $licensing_secret_key);
                $data_encoded = wordwrap($data_encoded, 80, "\n", true);
                $results['localkey'] = $data_encoded;
            }
            $results['remotecheck'] = true;
        }
        unset($postfields,$data,$matches,$whmcsurl,$licensing_secret_key,$checkdate,$usersip,$localkeydays,$allowcheckfaildays,$md5hash);

        return $results;
    }

    /**
     * Verifies if a license key is stored, if none is stored it builds and saves one
     *
     * @param string $licensekey - the license key provided
     *
     * @return string
     */
    final public static function save_license($licensekey) {
        $localkey = base64_decode(get_option('lkn_give_getnet_key', ''), true);
        $validatedkey = base64_decode(get_option('lkn_give_getnet_validated_key', ''), true);
        if (empty($licensekey)) {
            return 'Empty license';
        }
        if (empty($localkey)) {
            // First validation
            $results = self::validate_license($licensekey);
            if (isset($results['localkey'])) {
                $localkey = base64_encode($results['localkey']);
                update_option('lkn_give_getnet_key', $localkey);
            }
        } else {
            if ($licensekey === $validatedkey) {
                // Same key use localkey validation
                $results = self::validate_license($licensekey, $localkey);
            } else {
                // Key is different
                // Validate new key
                delete_option('validated_key');
                $results = self::validate_license($licensekey);
                if (isset($results['localkey'])) {
                    $localkey = base64_encode($results['localkey']);
                    update_option('lkn_give_getnet_key', $localkey);
                }
            }
        }
        // Interpret response
        switch ($results['status']) {
            case 'Active':
                $validatedkey = base64_encode($licensekey);
                update_option('validated_key', $validatedkey);
            case 'Invalid':
                return 'Invalid license - ' . var_export($results, true) . ' TxtFile: ' . $licensekey . '::' . $localkey;
            case 'Expired':
                return 'Expired license - ' . var_export($results, true);
            case 'Suspended':
                return 'Suspended license - ' . var_export($results, true);
            default:
                return 'Error on license checking verify the server - ' . var_export($results, true);
        }
    }
}