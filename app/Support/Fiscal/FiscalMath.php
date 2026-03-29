<?php

namespace App\Support\Fiscal;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

class FiscalMath
{
    public const string IVA_GENERAL_PCT = '21.00';

    public static function money(mixed $value): string
    {
        return self::decimal($value)
            ->toScale(2, RoundingMode::HALF_UP)
            ->__toString();
    }

    /**
     * @return array{
     *     monto_final:string,
     *     monto_sin_impuestos_nacionales:string,
     *     iva_contenido:string,
     *     iva_alicuota_pct:string,
     *     otros_impuestos_nacionales_indirectos:string
     * }
     */
    public static function desglosarMontoFinalGravadoConIva(
        mixed $montoFinal,
        mixed $ivaAlicuotaPct = self::IVA_GENERAL_PCT,
    ): array {
        $total = self::decimal($montoFinal)->toScale(2, RoundingMode::HALF_UP);
        $alicuota = self::decimal($ivaAlicuotaPct)->toScale(2, RoundingMode::HALF_UP);

        if ($total->isLessThan(BigDecimal::zero())) {
            throw new InvalidArgumentException('El monto final no puede ser negativo.');
        }

        if ($alicuota->isLessThan(BigDecimal::zero())) {
            throw new InvalidArgumentException('La alicuota de IVA no puede ser negativa.');
        }

        if ($total->isEqualTo(BigDecimal::zero()) || $alicuota->isEqualTo(BigDecimal::zero())) {
            return [
                'monto_final' => self::money($total),
                'monto_sin_impuestos_nacionales' => self::money($total),
                'iva_contenido' => self::money('0'),
                'iva_alicuota_pct' => self::money($alicuota),
                'otros_impuestos_nacionales_indirectos' => self::money('0'),
            ];
        }

        $factor = BigDecimal::one()->plus(
            $alicuota->dividedBy('100', 10, RoundingMode::HALF_UP),
        );
        $neto = $total->dividedBy($factor, 2, RoundingMode::HALF_UP);
        $iva = $total->minus($neto)->toScale(2, RoundingMode::HALF_UP);

        return [
            'monto_final' => self::money($total),
            'monto_sin_impuestos_nacionales' => self::money($neto),
            'iva_contenido' => self::money($iva),
            'iva_alicuota_pct' => self::money($alicuota),
            'otros_impuestos_nacionales_indirectos' => self::money('0'),
        ];
    }

    public static function multiplyMoney(mixed $left, mixed $right): string
    {
        return self::money(
            self::decimal($left)->multipliedBy(self::decimal($right)),
        );
    }

    protected static function decimal(mixed $value): BigDecimal
    {
        if ($value instanceof BigDecimal) {
            return $value;
        }

        return BigDecimal::of((string) ($value ?? '0'));
    }
}
