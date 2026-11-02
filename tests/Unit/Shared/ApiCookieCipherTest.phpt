<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Shared\Infrastructure\ApiCookieCipher;
use Tester\Assert;

$cipher = new ApiCookieCipher('secreto-de-prueba-con-suficiente-entropia');

$payload = 'teleproxy_admin=abc123';
$encrypted = $cipher->encrypt($payload);

Assert::notSame($payload, $encrypted);
Assert::notSame($encrypted, $cipher->encrypt($payload));
Assert::same($payload, $cipher->decrypt($encrypted));

Assert::null($cipher->decrypt('no-es-base64!!'));
Assert::null($cipher->decrypt('AAAA'));

$raw = base64_decode($encrypted, true);
Assert::type('string', $raw);
$raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 0xFF);
Assert::null($cipher->decrypt(base64_encode($raw)));

$other = new ApiCookieCipher('otro-secreto-totalmente-distinto');
Assert::null($other->decrypt($encrypted));
