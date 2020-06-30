<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Factory;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent;
use PHPUnit\Framework\TestCase;

/**
 * The keys used in this class are generated solely for the purpose of testing, do not expect funds there.
 *
 * BIP39 Mnemonic from https://iancoleman.io/bip39/#english
 * blanket feel weird account embody turtle trial upon east legal top suggest beach clump depth
 *
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent
 *
 * @internal
 */
final class AddressFromMasterPublicKeyComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (PHP_INT_SIZE !== 8) {
            self::markTestSkipped('unsupported on non 64 bits systems');
        }
    }

    /**
     * @dataProvider providerOfScenarios
     * @covers ::derive
     */
    public function testDerive(string $xpub, array $expectedAddressList): void
    {
        $factory = new AddressFromMasterPublicKeyComponent();
        foreach ($expectedAddressList as $index => $expectedAddress) {
            static::assertSame(
                $expectedAddress,
                $factory->derive($xpub, '0/'.$index)
            );
        }
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithEmptyXpubKey(): void
    {
        $factory = new AddressFromMasterPublicKeyComponent();
        $this->expectException(\InvalidArgumentException::class);
        $factory->derive('');
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithUnsupportedKey(): void
    {
        $factory = new AddressFromMasterPublicKeyComponent();
        $this->expectException(\RuntimeException::class);
        $factory->derive('(╯°□°）╯︵ ┻━┻');
    }

    public function providerOfScenarios(): array
    {
        return [
            'bip32' => [
                'xpub6Ad4AdCQVqoE1y15Mw7GTvKroWr3ayV1vDhjVJPHWuWKf4oJTqFg1wCtyVAYhYUueyzzR6UVfPFiNfeK2j9DTwqBi9CZuwj89xDqcFcqTWD',
                [
                    '1AVfKwWvNz5oNUzTe4eVs6NEwgW3YogG51',
                    '1A27dPhtdjukbwcrUTve9dhhAnba6WsCbF',
                    '1JdiM3TjY7JkJLAzCb69YJp2grSTzZHaqa',
                    '1Q9j57M2bpzkU5Lgdw4YynZogQCo8huLJj',
                    '1BeNDHT1dQJtaEzjApxh2rErVXE6XPgQmJ',
                    '1Do9QX7SgLYLq7VmoFzWAweQdg286ejEe5',
                    '16bWjWhD2a4eY9ww8vJ6s2oRtywqV3yBpR',
                    '1FN39xE4T9ZZk6bkJde2yNkiizJKmJmcWW',
                    '1NXxkM282oFhGhppxaoapLau4hS9z6kriz',
                    '13PFFwxfVAmTRNd16sseL3xMzLKChhUcp7',
                ],
            ],
            'bip44' => [
                'xpub6FLC3vkoMjp8a6BD87iCBcp4hLFwQ5AEHTpFw5rvR1BntuMFWwVCcynTvjDxNP4PuKoCmtC1e1trjn7QXRBNKbwybEMyWzgzHeqWVcf4A3L',
                [
                    '1JhSczvN9bq9HMDyET1R4YTbKNiDBcNnnG',
                    '18GaVowxPAS6QufJikH1JrziP6TVekp4C4',
                    '1F2qiBPQkLMdhehoETHfnYtbDDtH8bGPb1',
                    '1ALZpocN4ZLR76Gwr1qRpazGj4Bq1aQW3t',
                    '1DrFzb9AYrvNxaT3iaWCZdqr32ZPKRzS5e',
                    '1NvwodRXuMUi4KoSQ3BoqcQKFpSbDFpsCi',
                    '19aVDVAyy4uQhSbG9mS5gZ86W4Qp5ejx4r',
                    '1JUhzxb875ov5CePfFzCuAT6WpKJ55EsX',
                    '13gmptsEHrhge1TkUmkFpG1dUDxYyCikTs',
                    '144GJ6sqjuSZU8ULqemHbyBBKqKAkbyrkk',
                ],
            ],
            'bip49' => [
                'ypub6Y4RxNmNrdnwdwxERYnXa9rGd4upqeeJ3ixkJQUCQL8UcwYtXj86eXS5fVGU5xsmuuwRp3pKcdci89yiCmA9t2Mhi8cyEDD5P6w2NbfmWqT',
                [
                    '3AT3tf4cVfGRaQ87HpGQppTYmMrb5kpGQb', // m/49'/0'/0'/0/0
                    '3F2qLs6mBhCxZZymzNbSaRRKd6hNReBuzg', // m/49'/0'/0'/0/1
                    '32NUDuhL94mUyEdcHjUgcYBR1uC4PkhZyz', // m/49'/0'/0'/0/2
                    '36oai8ADEb7VrHXFDaYbn85zuEUafTCXWr', // m/49'/0'/0'/0/3
                    '33Wdf5YNiYCnWULu2EAxx1hhqDrbCpPJEt', // m/49'/0'/0'/0/4
                    '3FnbSsbzDX8B3Wa6A3y9QQRjSSMcEGsr6V', // m/49'/0'/0'/0/5
                    '3AfX69DKZzWV3fQCoVs4eDuYT1uCC1MG1v', // m/49'/0'/0'/0/6
                    '3McXP7bjBpu5tW8tyqY25oM81gfE79SSf3', // m/49'/0'/0'/0/7
                    '3Ew7BTJVm65CtP56eRYLM42UnLqUHNkRcC', // m/49'/0'/0'/0/8
                    '3B6UBBJ33RPRkLqMtUMBFvj8nhnWCmbTd6', // m/49'/0'/0'/0/9
                    '39qfz5ZzFrQMun8zkvo6FwGZezaKaa6GsL', // m/49'/0'/0'/0/10
                ],
            ],
            'bip49_bip32' => [
                'ypub6ZrznBaYeadopXBNAcm61Zj6Ke6dsMwcSFVNUixwWnGdgAtzdDy3SQhVKLYLYFmWUSqVSJSWZsXBFMYqJUAvrnzmFbZZVXamB3hvf1J54fQ',
                [
                    '3M1f3aKLy24pnH11y1zUdty4k2s3ZxYWUu',
                    '3Hm4bkbW7KCiymYctvXeTqCsxqG6f1ud5M',
                    '3G4thm9uNMBGoa3teHSqEWm15Frnn34Pe1',
                    '34PFKmJMEBcjNRprayqJRhm2iMuGgHtYRu',
                    '3Pq7HtdxVHQDHuCJH1G12YJB7RSsPAJHZS',
                    '33kbnvywFxE7sGpyAFxT1Jos2kDJvidfDw',
                    '3CwWsRQEFBfDi7aoyV24UcnQGFAQDr97fD',
                    '32Ne2wtZ466vPsZ3AW61msAtMqgSsNaG1N',
                    '37xD2QwHkCUJ6sJzLga7drBmKk2jwkh6Ks',
                    '3HtGqcM1c1t73vTt6Drp3hJt1PTBRauVf2',
                ],
            ],
            'bip84' => [
                'zpub6rLtzSoXnXKPXHroRKGCwuRVHjgA5YL6oUkdZnCfbDLdtAKNXb1FX1EmPUYR1uYMRBpngvkdJwxqhLvM46trRy5MRb7oYdSLbb4w5VC4i3z',
                [
                    'bc1qvqatyv2xynyanrej2fcutj6w5yugy0gc9jx2nn', // m/84'/0'/0'/0/0
                    'bc1q360p67y3jvards9f2eud5rlu07q8ampfp35vp7', // m/84'/0'/0'/0/1
                    'bc1qs4k3p9w4ke5np3lr3lgnma9jcaxedau8mpwawu', // m/84'/0'/0'/0/2
                    'bc1qpk48z0s7gvyrupm2wmd7nr0fdzkxa42372ver2', // m/84'/0'/0'/0/3
                    'bc1q0uam3l30y43q0wjhq0kwf050uyg23mz7p3frr4', // m/84'/0'/0'/0/4
                    'bc1qef62h9xt937lu9x5ydv204r7lpk3sjdc575kax', // m/84'/0'/0'/0/5
                    'bc1q2rl0he7zca8a88ax7hf9259c33kd2ux5ffhkqw', // m/84'/0'/0'/0/6
                    'bc1qr9ffza3w6tae4g5m4ydnjvphg8tpgarf5yjgqz', // m/84'/0'/0'/0/7
                    'bc1qr65srxamrmx8zumgv5puljnd93u3sj7lw6cnrg', // m/84'/0'/0'/0/8
                    'bc1q2ufc8j9uw6x7hwqfsdakungk63etanxtkplel0', // m/84'/0'/0'/0/9
                    'bc1qlxyzn8tpjm6p8swkhuwkztutapv5ehtdkv83y2', // m/84'/0'/0'/0/10
                ],
            ],
            'bip84_bip32' => [
                'zpub6tvRfzF8bFbWjy9L88dB7TjhrZZCqqWMXX74n7Kg6sj4v6JHngNE3Yb7Mwrd5HFfU3PpY5d3vW2Fg3RUjwaV66VLyazmypAqCUY9fwhX3Wr',
                [
                    'bc1ql302thvndyuglstvya590ltq3l7qzhd4j28up3',
                    'bc1qngzwtcaqd7wkvgrhw583y66z6tj25gkykakajp',
                    'bc1qnnnvlq9du2taf2medtm8lax84p5en623eycs6x',
                    'bc1qxp5r6p2xy3gwm0cl2y0ne08xk7ujxysszqza68',
                    'bc1qpx80hnvcfel7x3hnd5w9rzd6nza0dsamkxv2zf',
                    'bc1qynvjuf65vgqfrwqzuayvc6fvfyrz356uwu2c5k',
                    'bc1qxj9zapryqr5xv9fz7vzew0ykdnwc045zye9tjs',
                    'bc1qm800jhqqalmluwjx84y8zjqy867f8pw8nsye3q',
                    'bc1qf2xye84uymezpfnwzhmddewvl2apqza4nk9cee',
                    'bc1qhjlzmw9ze6xzlhxtgkaysfqwkljkxerdnt4xhv',
                ],
            ],
        ];
    }
}
