<?php

namespace App\Termwind;

use Symfony\Component\Console\Helper\QuestionHelper as SymfonyQuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question as SymfonyQuestion;
use Termwind\Helpers\QuestionHelper;
use Termwind\HtmlRenderer;
use Termwind\Termwind;

class Terminal
{
    /**
     * The streamable input to receive the input from the user.
     */
    private static StreamableInputInterface|null $streamableInput;

    /**
     * An instance of Symfony's question helper.
     */
    private SymfonyQuestionHelper $helper;

    public function __construct(SymfonyQuestionHelper $helper = null)
    {
        $this->helper = $helper ?? new QuestionHelper();
    }

    /**
     * Gets the streamable input implementation.
     */
    public static function getStreamableInput(): StreamableInputInterface
    {
        return self::$streamableInput ??= new ArgvInput();
    }

    public function render(string $html): void
    {
        (new HtmlRenderer())->render($html, OutputInterface::OUTPUT_NORMAL);
    }

    /**
     * Renders a prompt to the user.
     */
    public function ask(string $question, string $default = null, bool $allowEmpty = false): mixed
    {
        $view = view('question')->with([
            'question' => $question,
            'default' => $default,
            'allowEmpty' => $allowEmpty,
        ]);

        $html = (new HtmlRenderer)->parse($view)->toString();

        return $this->helper->ask(
            self::getStreamableInput(),
            Termwind::getRenderer(),
            new SymfonyQuestion($html, $default)
        );
    }

    /**
     * Renders a choice to the user.
     */
    public function choose(string $question, array $choices, string $default = null, bool $allowEmpty = false): mixed
    {
        $view = view('question')->with([
            'question' => $question,
            'default' => $default,
            'allowEmpty' => $allowEmpty,
            'choices' => $choices,
        ]);

        $html = (new HtmlRenderer)->parse($view)->toString();

        $question = new SymfonyQuestion($html, $default);
        $question->setAutocompleterValues($choices);

        return $this->helper->ask(
            self::getStreamableInput(),
            Termwind::getRenderer(),
            $question
        );
    }
}
