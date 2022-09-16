<?php

use App\Terminal\Terminal;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

test('render', function () {
    $output = fakeConsoleRenderer();
    $terminal = new Terminal();

    $terminal->render('<div class="m-2"><span class="bg-blue text-red">foo</span><div>');

    expect(collect($output->output)->join(""))->toMatchSnapshot();
});

test('success banner', function () {
    $output = fakeConsoleRenderer();
    $terminal = new Terminal();

    $terminal->successBanner('yahoo!');

    expect(collect($output->output)->join(''))->toMatchSnapshot();
});

test('error', function () {
    $output = fakeConsoleRenderer();
    $terminal = new Terminal();

    $terminal->error('damn!');

    expect(collect($output->output)->join("\n"))->toMatchSnapshot();
});

test('ask', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->ask('foo bar'))->toBe('ok');
});

test('ask with default', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->ask('foo bar', 'baz'))->toBe('ok');
});

test('ask with default and allow empty', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->ask('foo bar', 'baz', true))->toBe('ok');
});

test('choose', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot()
                ->and($question->getAutocompleterValues())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->choose('foo bar', ['baz', 'qux']))->toBe('ok');
});

test('choose with default', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot()
                ->and($question->getAutocompleterValues())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->choose('foo bar', ['baz', 'qux'], 'qux'))->toBe('ok');
});

test('choose with default and empty allowed', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot()
                ->and($question->getAutocompleterValues())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->choose('foo bar', ['baz', 'qux'], 'qux', true))->toBe('ok');
});

test('choose with default and custom empty allowed', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot()
                ->and($question->getAutocompleterValues())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->choose('foo bar', ['baz', 'qux'], 'qux', 'none'))->toBe('ok');
});

test('choose with default and enter empty allowed', function () {
    $helper = new class extends QuestionHelper
    {
        public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
        {
            expect($question->getQuestion())->toMatchSnapshot()
                ->and($question->getAutocompleterValues())->toMatchSnapshot();

            return 'ok';
        }
    };

    $terminal = new Terminal($helper);

    expect($terminal->choose('foo bar', ['baz', 'qux'], 'qux', ''))->toBe('ok');
});
