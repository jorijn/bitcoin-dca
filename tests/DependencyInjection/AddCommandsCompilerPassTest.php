<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\DependencyInjection;

use Jorijn\Bl3pDca\DependencyInjection\AddCommandsCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\DependencyInjection\AddCommandsCompilerPass
 *
 * @internal
 */
final class AddCommandsCompilerPassTest extends TestCase
{
    /** @var AddCommandsCompilerPass */
    private AddCommandsCompilerPass $pass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pass = new AddCommandsCompilerPass();
    }

    public function testNoTaggedServicesAvailable(): void
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects(static::once())
            ->method('findTaggedServiceIds')
            ->willReturn([])
        ;

        $containerBuilder->expects(static::never())->method('findDefinition');

        $this->pass->process($containerBuilder);
    }

    public function testTaggedServicesAreRegisteredInOrder(): void
    {
        $taggedServices = [
            'id1' => [['priority' => 10]],
            'id2' => [['priority' => 20]],
        ];

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects(static::once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices)
        ;

        $applicationDefinition = $this->createMock(Definition::class);
        $containerBuilder->method('findDefinition')->willReturnMap([['dca.application', $applicationDefinition]]);

        $assertExpression = static function (string $id) {
            return self::callback(static function (array $arguments) use ($id) {
                self::assertArrayHasKey(0, $arguments);
                self::assertInstanceOf(Reference::class, $arguments[0]);
                self::assertSame($id, (string) $arguments[0]);

                return true;
            });
        };

        $applicationDefinition
            ->expects(static::exactly(\count($taggedServices)))
            ->method('addMethodCall')
            ->withConsecutive(
                ['add', $assertExpression('id2')],
                ['add', $assertExpression('id1')],
            )
        ;

        $this->pass->process($containerBuilder);
    }
}
