<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Discord;

use App\Service\Discord\DiscordSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class DiscordSignatureVerifierTest extends TestCase
{
    private string $publicKeyHex;
    private string $secretKey;

    protected function setUp(): void
    {
        $keypair = sodium_crypto_sign_keypair();
        $this->publicKeyHex = sodium_bin2hex(sodium_crypto_sign_publickey($keypair));
        $this->secretKey = sodium_crypto_sign_secretkey($keypair);
    }

    public function testSignatureValide(): void
    {
        $body = '{"type":1}';
        $signature = sodium_bin2hex(sodium_crypto_sign_detached('1700000000' . $body, $this->secretKey));

        $this->assertTrue($this->verifier()->isValid($body, $signature, '1700000000'));
    }

    public function testCorpsModifieEstRefuse(): void
    {
        $signature = sodium_bin2hex(sodium_crypto_sign_detached('1700000000{"type":1}', $this->secretKey));

        $this->assertFalse($this->verifier()->isValid('{"type":2}', $signature, '1700000000'));
    }

    public function testHorodatageModifieEstRefuse(): void
    {
        $signature = sodium_bin2hex(sodium_crypto_sign_detached('1700000000{"type":1}', $this->secretKey));

        $this->assertFalse($this->verifier()->isValid('{"type":1}', $signature, '1700000001'));
    }

    public function testEnTetesAbsentsOuMalFormesSontRefuses(): void
    {
        $verifier = $this->verifier();

        $this->assertFalse($verifier->isValid('{}', null, '1700000000'));
        $this->assertFalse($verifier->isValid('{}', 'abcd', null));
        $this->assertFalse($verifier->isValid('{}', 'pas-de-l-hexadecimal', '1700000000'));
    }

    public function testCleDeConfigurationInvalideRefuseToutSansErreur(): void
    {
        $signature = sodium_bin2hex(sodium_crypto_sign_detached('1700000000{}', $this->secretKey));

        $this->assertFalse((new DiscordSignatureVerifier('todo'))->isValid('{}', $signature, '1700000000'));
    }

    private function verifier(): DiscordSignatureVerifier
    {
        return new DiscordSignatureVerifier($this->publicKeyHex);
    }
}
