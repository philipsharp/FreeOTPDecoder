<?php
/**
 * FreeOTP Decoder
 *
 * @author Philip Sharp <philip@kerzap.com>
 * @copyright 2015 Philip Sharp
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if ($argc != 2) {
    help();
    exit(1);
}

$file = $argv[1];

if (!is_readable($file)) {
    echo "Cannot read '$file'."  .PHP_EOL;
    exit(1);
}

$xml = simplexml_load_file($file);

if (!$xml) {
    echo "Cannot load XML token file." . PHP_EOL;
    exit(1);
}

if (count($xml->string) == 0) {
    echo "Invalid XML token file." . PHP_EOL;
    exit(1);
}

foreach($xml->string as $s) {
    $name = (string)$s['name'];
    $json = (string)$s;
    $data = json_decode($json);
    if (!$data) { // bad JSON
        echo "$name: invalid data" . PHP_EOL;
	    continue;
    }
    if (!isset($data->secret)) { // not a token
        continue;
    }
    echo $name . ': ';
    echo makeUri($data);
    echo PHP_EOL;
}

function help() {
    global $argv;
    echo "Usage: php {$argv[0]} tokens-xml-file" . PHP_EOL;
    echo PHP_EOL;
}

/**
 * Convert FreeOTP token data into standard otpauth URI
 *
 * Based on the Token::toURI() method from FreeOTP
 *
 * @param stdObject $token FreeOTP token data structure
 * @return string otpauth URI
 */
function makeUri($token) {
    $label = isset($token->issuerExt) ? $token->issuerExt . ':' . $token->label: $token->label;
    $baseUri = 'otpauth://' . urlencode(strtolower($token->type)) . '/' . urlencode($label);
    $params = [
        'secret'    => encodeSecretBytes($token->secret),
        'issuer'    => isset($token->issuerInt) ? $token->issuerInt : $token->issuerExt,
        'algorithm' => $token->algo,
        'digits'    => $token->digits,
        'period'    => $token->period,
    ];
    return $baseUri . '?' . http_build_query($params);
}

/**
 * Convert stored secret into otpauth URI parameter
 *
 * Port of encodeInternal() method from Google Authenticator via FreeOTP
 * @link https://fedorahosted.org/freeotp/browser/android/app/src/main/java/com/google/android/apps/authenticator/Base32String.java
 *
 * @param array $data Internal representation of the secret as byte array
 * @return string Secret as Base32 encode string
 */
function encodeSecretBytes($data) {
    if (empty($data)) {
        return '';
    }
    
    $DIGITS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // 32 chars
    $SHIFT = 5; // trailing zeros in binary representation of the size of the alphabet
    $MASK = 31; // one less than size of alphabet
    
    // SHIFT is the number of bits per output character, so the length of the
    // output is the length of the input multiplied by 8/SHIFT, rounded up.
    if (count($data) >= (1 << 28)) {
        // The computation below will fail, so don't do it.
        throw new UnexpectedValueException('Bad Secret');
    }

    $outputLength = (count($data) * 8 + $SHIFT - 1) / $SHIFT;
    $result = '';

    $buffer = $data[0];
    $next = 1;
    $bitsLeft = 8;
    while ($bitsLeft > 0 || $next < count($data)) {
        if ($bitsLeft < $SHIFT) {
            if ($next < count($data)) {
                $buffer <<= 8;
                $buffer |= ($data[$next++] & 0xff);
                $bitsLeft += 8;
            } else {
                $pad = $SHIFT - $bitsLeft;
                $buffer <<= $pad;
                $bitsLeft += $pad;
            }
        }
        $index = $MASK & ($buffer >> ($bitsLeft - $SHIFT));
        $bitsLeft -= $SHIFT;
        $result .= $DIGITS[$index];
    }
    return $result;
}
