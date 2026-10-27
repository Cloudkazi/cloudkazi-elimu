<?php

namespace App\Services;

class KenyanPayrollCalculator
{
    /**
     * Compute full Kenyan payroll breakdown for an employee.
     *
     * @param float $basicSalary
     * @param array $allowances [name => amount, ...]
     * @param array $otherDeductions [name => amount, ...]
     * @return array
     */
    public static function calculate(float $basicSalary, array $allowances = [], array $otherDeductions = []): array
    {
        $basicSalary = max(0.0, round($basicSalary, 2));

        // Allowances
        $totalAllowances = 0.0;
        foreach ($allowances as $amount) {
            $totalAllowances += max(0.0, (float)$amount);
        }
        $totalAllowances = round($totalAllowances, 2);

        $grossSalary = round($basicSalary + $totalAllowances, 2);

        // 1. NSSF (Act 2013 rates, standard Tier I & Tier II)
        // Tier I: 6% of earnings up to LEL (KES 8,000) -> max KES 480
        // Tier II: 6% of earnings between LEL (8,000) and UEL (72,000) -> max KES 3,840
        $tierILimit = 8000.0;
        $tierIILimit = 72000.0;

        $pensionablePay = $grossSalary;

        $tierIEarnings = min($pensionablePay, $tierILimit);
        $nssfTierI = round($tierIEarnings * 0.06, 2);

        $nssfTierII = 0.0;
        if ($pensionablePay > $tierILimit) {
            $tierIIEarnings = min($pensionablePay - $tierILimit, $tierIILimit - $tierILimit);
            $nssfTierII = round($tierIIEarnings * 0.06, 2);
        }

        $nssfEmployee = round($nssfTierI + $nssfTierII, 2);
        // Employer matches 100%
        $nssfEmployer = $nssfEmployee;

        // 2. SHIF (Social Health Insurance Fund)
        // 2.75% of Gross Pay, minimum KES 300
        $shif = 0.0;
        if ($grossSalary > 0) {
            $shif = max(300.0, round($grossSalary * 0.0275, 2));
        }

        // 3. Affordable Housing Levy (AHL)
        // 1.5% employee, 1.5% employer
        $housingLevyEmployee = round($grossSalary * 0.015, 2);
        $housingLevyEmployer = round($grossSalary * 0.015, 2);

        // 4. PAYE
        // Taxable Pay = Gross - Allowable deductions (NSSF Employee)
        $taxablePay = max(0.0, round($grossSalary - $nssfEmployee, 2));

        $taxBeforeRelief = 0.0;
        $remainingTaxable = $taxablePay;

        // Band 1: First 24,000 @ 10%
        if ($remainingTaxable > 0) {
            $chunk = min($remainingTaxable, 24000.0);
            $taxBeforeRelief += $chunk * 0.10;
            $remainingTaxable -= $chunk;
        }

        // Band 2: Next 8,333 (24,001 - 32,333) @ 25%
        if ($remainingTaxable > 0) {
            $chunk = min($remainingTaxable, 8333.33);
            $taxBeforeRelief += $chunk * 0.25;
            $remainingTaxable -= $chunk;
        }

        // Band 3: Next 467,666.67 (32,334 - 500,000) @ 30%
        if ($remainingTaxable > 0) {
            $chunk = min($remainingTaxable, 467666.67);
            $taxBeforeRelief += $chunk * 0.30;
            $remainingTaxable -= $chunk;
        }

        // Band 4: Next 300,000 (500,001 - 800,000) @ 32.5%
        if ($remainingTaxable > 0) {
            $chunk = min($remainingTaxable, 300000.0);
            $taxBeforeRelief += $chunk * 0.325;
            $remainingTaxable -= $chunk;
        }

        // Band 5: Above 800,000 @ 35%
        if ($remainingTaxable > 0) {
            $taxBeforeRelief += $remainingTaxable * 0.35;
        }

        $taxBeforeRelief = round($taxBeforeRelief, 2);

        // Reliefs
        $personalRelief = ($grossSalary > 0) ? 2400.00 : 0.0;
        // SHIF/NHIF Insurance Relief: 15% of contribution, capped at KES 5,000/mo
        $insuranceRelief = round(min(5000.0, $shif * 0.15), 2);

        $totalRelief = $personalRelief + $insuranceRelief;
        $paye = max(0.0, round($taxBeforeRelief - $totalRelief, 2));

        // 5. Other Deductions
        $totalOtherDeductions = 0.0;
        foreach ($otherDeductions as $amount) {
            $totalOtherDeductions += max(0.0, (float)$amount);
        }
        $totalOtherDeductions = round($totalOtherDeductions, 2);

        // Totals
        $statutoryDeductions = round($nssfEmployee + $shif + $housingLevyEmployee + $paye, 2);
        $totalDeductions = round($statutoryDeductions + $totalOtherDeductions, 2);

        $netSalary = max(0.0, round($grossSalary - $totalDeductions, 2));

        $totalEmployerContributions = round($nssfEmployer + $housingLevyEmployer, 2);

        return [
            'basic_salary' => $basicSalary,
            'allowances' => $totalAllowances,
            'gross_salary' => $grossSalary,
            'taxable_income' => $taxablePay,
            'tax_before_relief' => $taxBeforeRelief,
            'personal_relief' => $personalRelief,
            'insurance_relief' => $insuranceRelief,
            'paye' => $paye,
            'nssf_tier_1' => $nssfTierI,
            'nssf_tier_2' => $nssfTierII,
            'nssf_employee' => $nssfEmployee,
            'nssf_employer' => $nssfEmployer,
            'shif' => $shif,
            'housing_levy_employee' => $housingLevyEmployee,
            'housing_levy_employer' => $housingLevyEmployer,
            'other_deductions' => $totalOtherDeductions,
            'statutory_deductions' => $statutoryDeductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'total_employer_contributions' => $totalEmployerContributions,
        ];
    }
}
