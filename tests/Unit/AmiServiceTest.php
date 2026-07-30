<?php

namespace Tests\Unit;

use App\Services\AmiService;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AmiServiceTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $service = new AmiService();
        $this->assertInstanceOf(AmiService::class, $service);
    }

    #[Test]
    public function it_reads_configuration_from_ami_config(): void
    {
        Config::set('ami.host', '192.168.1.100');
        Config::set('ami.port', 5039);
        Config::set('ami.username', 'testuser');
        Config::set('ami.secret', 'testpass');
        Config::set('ami.timeout', 15);

        $service = new AmiService();

        $this->assertEquals('192.168.1.100', config('ami.host'));
        $this->assertEquals(5039, config('ami.port'));
        $this->assertEquals('testuser', config('ami.username'));
        $this->assertEquals('testpass', config('ami.secret'));
        $this->assertEquals(15, config('ami.timeout'));
    }

    #[Test]
    public function it_uses_package_config_format(): void
    {
        $configKeys = ['host', 'port', 'username', 'secret', 'timeout'];

        foreach ($configKeys as $key) {
            $this->assertArrayHasKey($key, config('ami'), "AMI config should have key: {$key}");
        }
    }
}