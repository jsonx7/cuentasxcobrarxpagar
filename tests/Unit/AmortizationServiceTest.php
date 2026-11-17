<?php

namespace Tests\Unit;

use App\Enums\PaymentFrequency;
use App\Services\AmortizationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AmortizationServiceTest extends TestCase
{
    #[Test]
    public function it_builds_the_same_monthly_plan_as_the_excel_model(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 1500,
            tasaInteresAnual: 8,
            plazo: 30,
            frecuencia: PaymentFrequency::Mensual,
            fechaPrimerPago: '2026-01-08',
        );

        $this->assertCount(30, $plan['rows']);
        $this->assertEqualsWithDelta(55.33, $plan['cuota'], 0.01);
        $this->assertEqualsWithDelta(10.00, $plan['rows'][0]['interes'], 0.001);
        $this->assertEqualsWithDelta(1454.67, $plan['rows'][0]['saldo_pendiente'], 0.01);
        $this->assertEqualsWithDelta(1500, $plan['total_a_pagar'] - $plan['interes_total'], 0.02);
        $this->assertEqualsWithDelta(0, $plan['rows'][29]['saldo_pendiente'], 0.001);
        $this->assertSame('2026-01-08', $plan['rows'][0]['fecha_vencimiento']);
        $this->assertSame('2026-02-08', $plan['rows'][1]['fecha_vencimiento']);
    }

    #[Test]
    public function it_matches_the_monthly_amortization_used_in_the_mora_examples(): void
    {
        $service = app(AmortizationService::class);

        $plan = $service->build(
            monto: 40000,
            tasaInteresAnual: 9,
            plazo: 36,
            frecuencia: PaymentFrequency::Mensual,
            fechaPrimerPago: '2026-01-31',
        );

        $this->assertEqualsWithDelta(1271.99, $plan['cuota'], 0.01);
        $this->assertEqualsWithDelta(300.00, $plan['rows'][0]['interes'], 0.001);
        $this->assertEqualsWithDelta(971.99, $plan['rows'][0]['capital'], 0.01);
        $this->assertEqualsWithDelta(39028.01, $plan['rows'][0]['saldo_pendiente'], 0.01);
        $this->assertSame('2026-02-28', $plan['rows'][1]['fecha_vencimiento']);
        $this->assertSame('2026-03-31', $plan['rows'][2]['fecha_vencimiento']);

        $plan = $service->build(
            monto: 64509,
            tasaInteresAnual: 10,
            plazo: 60,
            frecuencia: PaymentFrequency::Mensual,
            fechaPrimerPago: '2026-01-31',
        );

        $this->assertEqualsWithDelta(1370.63, $plan['cuota'], 0.01);
        $this->assertEqualsWithDelta(537.58, $plan['rows'][0]['interes'], 0.001);
        $this->assertEqualsWithDelta(833.05, $plan['rows'][0]['capital'], 0.01);
        $this->assertEqualsWithDelta(63675.95, $plan['rows'][0]['saldo_pendiente'], 0.01);
    }

    #[Test]
    public function it_supports_a_zero_interest_loan(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 300,
            tasaInteresAnual: 0,
            plazo: 3,
            frecuencia: PaymentFrequency::Mensual,
            fechaPrimerPago: '2026-01-31',
        );

        $this->assertCount(3, $plan['rows']);
        $this->assertEqualsWithDelta(100, $plan['cuota'], 0.001);
        $this->assertEqualsWithDelta(0, $plan['interes_total'], 0.001);
        $this->assertEquals('2026-02-28', $plan['rows'][1]['fecha_vencimiento']);
        $this->assertEquals('2026-03-31', $plan['rows'][2]['fecha_vencimiento']);
    }

    #[Test]
    public function legacy_frequencies_still_generate_monthly_dates(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 1200,
            tasaInteresAnual: 8,
            plazo: 3,
            frecuencia: PaymentFrequency::Bisemanal,
            fechaPrimerPago: '2026-01-05',
        );

        $this->assertCount(3, $plan['rows']);
        $this->assertSame('2026-01-05', $plan['rows'][0]['fecha_vencimiento']);
        $this->assertSame('2026-02-05', $plan['rows'][1]['fecha_vencimiento']);
        $this->assertSame('2026-03-05', $plan['rows'][2]['fecha_vencimiento']);
    }

    #[Test]
    public function a_single_payment_forces_one_installment(): void
    {
        $plan = app(AmortizationService::class)->build(
            monto: 500,
            tasaInteresAnual: 12,
            plazo: 20,
            frecuencia: PaymentFrequency::Unico,
            fechaPrimerPago: '2026-02-01',
        );

        $this->assertCount(1, $plan['rows']);
        $this->assertEqualsWithDelta(500, $plan['total_a_pagar'] - $plan['interes_total'], 0.001);
    }
}
