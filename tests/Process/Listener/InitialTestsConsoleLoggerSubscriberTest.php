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

namespace Infection\Tests\Process\Listener;

use Infection\EventDispatcher\EventDispatcher;
use Infection\Events\InitialTestSuiteStarted;
use Infection\Process\Listener\InitialTestsConsoleLoggerSubscriber;
use Infection\TestFramework\AbstractTestFrameworkAdapter;
use Mockery;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class InitialTestsConsoleLoggerSubscriberTest extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function test_it_reacts_on_initial_test_suite_run(): void
    {
        $output = Mockery::mock(OutputInterface::class);
        $output->shouldReceive('isDecorated');
        $output->shouldReceive('writeln');
        $output->shouldReceive('getVerbosity')->andReturn(OutputInterface::VERBOSITY_QUIET);

        $testFramework = Mockery::mock(AbstractTestFrameworkAdapter::class);
        $testFramework->shouldReceive('getName')->once();
        $testFramework->shouldReceive('getVersion')->once();

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new InitialTestsConsoleLoggerSubscriber($output, $testFramework));

        $dispatcher->dispatch(new InitialTestSuiteStarted());
    }

    public function test_it_sets_test_framework_version_as_unknown_in_case_of_exception(): void
    {
        $output = Mockery::mock(OutputInterface::class);
        $output->shouldReceive('isDecorated');
        $output->shouldReceive('writeln')->once()->withArgs([[
            'Running initial test suite...',
            '',
            'PHPUnit version: unknown',
            '',
        ]]);
        $output->shouldReceive('getVerbosity')->andReturn(OutputInterface::VERBOSITY_QUIET);

        $testFramework = Mockery::mock(AbstractTestFrameworkAdapter::class);
        $testFramework->shouldReceive('getName')->once()->andReturn('PHPUnit');
        $testFramework->shouldReceive('getVersion')->andThrow(\InvalidArgumentException::class);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new InitialTestsConsoleLoggerSubscriber($output, $testFramework));

        $dispatcher->dispatch(new InitialTestSuiteStarted());
    }
}
