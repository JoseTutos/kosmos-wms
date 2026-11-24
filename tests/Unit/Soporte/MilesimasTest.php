<?php

namespace Tests\Unit\Soporte;

use App\Support\Milesimas;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MilesimasTest extends TestCase
{
    #[DataProvider('conversiones')]
    public function test_convierte_a_milesimas(string|int|float|null $valor, int $esperado): void
    {
        $this->assertSame($esperado, Milesimas::de($valor));
    }

    public static function conversiones(): array
    {
        return [
            ['12.5', 12500], ['12.500', 12500], ['-2.25', -2250], ['0', 0], ['0.001', 1],
            [3, 3000], [1.2, 1200], ['+7', 7000], [null, 0], ['', 0], ['99999999999.999', 99999999999999],
        ];
    }

    public function test_formatea_con_tres_decimales(): void
    {
        $this->assertSame('12.500', Milesimas::texto(12500));
        $this->assertSame('0.005', Milesimas::texto(5));
        $this->assertSame('-1.500', Milesimas::texto(-1500));
        $this->assertSame('0.000', Milesimas::texto(0));
    }

    #[DataProvider('invalidos')]
    public function test_rechaza_formatos_invalidos(string $valor): void
    {
        $this->expectException(InvalidArgumentException::class);
        Milesimas::de($valor);
    }

    public static function invalidos(): array
    {
        return [['1.2345'], ['1,5'], ['abc'], ['1.2.3']];
    }
}
