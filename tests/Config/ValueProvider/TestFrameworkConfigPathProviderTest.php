<?php
/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017-2018, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Infection\Tests\Config\ValueProvider;

use Infection\Config\ConsoleHelper;
use Infection\Config\ValueProvider\TestFrameworkConfigPathProvider;
use Infection\TestFramework\Config\TestFrameworkConfigLocatorInterface;
use Mockery;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class TestFrameworkConfigPathProviderTest extends AbstractBaseProviderTest
{
    public function test_it_calls_locator_in_the_current_dir(): void
    {
        $locatorMock = $this->createMock(TestFrameworkConfigLocatorInterface::class);
        $locatorMock->expects($this->once())->method('locate');

        $provider = new TestFrameworkConfigPathProvider(
            $locatorMock,
            $this->createMock(ConsoleHelper::class),
            $this->getQuestionHelper()
        );

        $result = $provider->get(
            $this->createStreamableInputInterfaceMock(),
            $this->createOutputInterface(),
            [],
            'phpunit'
        );

        $this->assertNull($result);
    }

    public function test_it_asks_question_if_no_config_is_found_in_current_dir(): void
    {
        $locatorMock = Mockery::mock(TestFrameworkConfigLocatorInterface::class);

        $locatorMock->shouldReceive('locate')->once()->andThrow(new \Exception());
        $locatorMock->shouldReceive('locate')->once()->andThrow(new \Exception());
        $locatorMock->shouldReceive('locate')->once()->andReturn(true);

        $consoleMock = $this->createMock(ConsoleHelper::class);
        $consoleMock->expects($this->once())->method('getQuestion')->willReturn('foobar');

        $provider = new TestFrameworkConfigPathProvider($locatorMock, $consoleMock, $this->getQuestionHelper());

        $inputPhpUnitPath = realpath(__DIR__ . '/../../Fixtures/Files/phpunit');

        $path = $provider->get(
            $this->createStreamableInputInterfaceMock($this->getInputStream("{$inputPhpUnitPath}\n")),
            $this->createOutputInterface(),
            [],
            'phpunit'
        );

        $this->assertSame($inputPhpUnitPath, $path);
        $this->assertDirectoryExists($path);
    }

    public function test_it_automatically_guesses_path(): void
    {
        $locatorMock = Mockery::mock(TestFrameworkConfigLocatorInterface::class);

        $locatorMock->shouldReceive('locate')->once()->andThrow(new \Exception());
        $locatorMock->shouldReceive('locate')->once()->andReturn(true);

        $consoleMock = Mockery::mock(ConsoleHelper::class);
        $consoleMock->shouldReceive('getQuestion')->never();

        $provider = new TestFrameworkConfigPathProvider($locatorMock, $consoleMock, $this->getQuestionHelper());

        $path = $provider->get(
            Mockery::mock(InputInterface::class),
            Mockery::mock(OutputInterface::class),
            [],
            'phpunit'
        );

        $this->assertSame('.', $path);
    }

    public function test_validates_incorrect_dir(): void
    {
        if (!$this->hasSttyAvailable()) {
            $this->markTestSkipped('Stty is not available');
        }

        $locatorMock = Mockery::mock(TestFrameworkConfigLocatorInterface::class);

        $locatorMock->shouldReceive('locate')->once()->andThrow(new \Exception());
        $locatorMock->shouldReceive('locate')->once()->andThrow(new \Exception());
        $locatorMock->shouldReceive('locate')->once()->andReturn(true);

        $consoleMock = $this->createMock(ConsoleHelper::class);
        $consoleMock->expects($this->once())->method('getQuestion')->willReturn('foobar');

        $provider = new TestFrameworkConfigPathProvider($locatorMock, $consoleMock, $this->getQuestionHelper());

        $path = $provider->get(
            $this->createStreamableInputInterfaceMock($this->getInputStream("abc\n")),
            $this->createOutputInterface(),
            [],
            'phpunit'
        );

        $this->assertSame('.', $path); // fallbacks to default value
    }
}
