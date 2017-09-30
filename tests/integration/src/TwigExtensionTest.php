<?php

declare(strict_types=1);

namespace Tests\Integration;

use Arachne\Codeception\Module\NetteDIModule;
use Codeception\Test\Unit;
use DateTime;
use Tracy\Dumper;
use Twig\Environment;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class TwigExtensionTest extends Unit
{
    /**
     * @var NetteDIModule
     */
    protected $tester;

    public function testConfiguration(): void
    {
        /* @var $twig Environment */
        $twig = $this->tester->grabService(Environment::class);
        $this->assertInstanceOf(Environment::class, $twig);

        // Fix dump result comparison on linux.
        Dumper::$terminalColors = [];

        $this->assertSame('"value" (5)', trim($twig->render('index.twig', ['foo' => 'value'])));

        $this->assertSame('"long_val ... " (10)', trim($twig->render('@namespace/index.twig', ['bar' => 'long_value'])));

        $this->assertInstanceOf(DateTime::class, $twig->getRuntime('DateTime'));
    }
}
