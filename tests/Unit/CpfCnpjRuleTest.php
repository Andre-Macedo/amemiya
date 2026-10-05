<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\CpfCnpj;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CpfCnpjRuleTest extends TestCase
{
    public function test_validates_valid_cpf(): void
    {
        // Gerador padrão de CPF válido de teste
        $validCpf = '52998224725';
        $validator = Validator::make(['document' => $validCpf], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->passes());

        // Com pontuação
        $validCpfFormatted = '529.982.247-25';
        $validator = Validator::make(['document' => $validCpfFormatted], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->passes());
    }

    public function test_fails_on_invalid_cpf(): void
    {
        $invalidCpf = '12345678900';
        $validator = Validator::make(['document' => $invalidCpf], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->fails());

        // Dígitos repetidos
        $repeatedCpf = '111.111.111-11';
        $validator = Validator::make(['document' => $repeatedCpf], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->fails());
    }

    public function test_validates_valid_cnpj(): void
    {
        $validCnpj = '11.222.333/0001-81';
        $validator = Validator::make(['document' => $validCnpj], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->passes());

        $validCnpjRaw = '11222333000181';
        $validator = Validator::make(['document' => $validCnpjRaw], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->passes());
    }

    public function test_fails_on_invalid_cnpj(): void
    {
        $invalidCnpj = '11.222.333/0001-99';
        $validator = Validator::make(['document' => $invalidCnpj], ['document' => [new CpfCnpj]]);
        $this->assertTrue($validator->fails());
    }
}
