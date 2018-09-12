<?php
namespace SevenEcks\Markdown;

use cebe\markdown\Parser;
/* use cebe\markdown\Markdown; */
use cebe\markdown\block\HeadlineTrait;
use cebe\markdown\block\CodeTrait as BlockCodeTrait;
use cebe\markdown\inline\CodeTrait as InlineCodeTrait;
use cebe\markdown\inline\EmphStrongTrait;
use SevenEcks\StringUtils\StringUtils;
use SevenEcks\Ansi\Colorize;
;
use cebe\markdown\block\ListTrait;
use cebe\markdown\block\QuoteTrait;
use cebe\markdown\block\RuleTrait;
use cebe\markdown\inline\LinkTrait;
use cebe\markdown\inline\StrikeoutTrait;
use cebe\markdown\inline\UrlLinkTrait;

class MarkdownTerminal extends Parser
{
    use HeadlineTrait;
    use InlineCodeTrait;
    use BlockCodeTrait;
    use ListTrait;
    use QuoteTrait;
    use RuleTrait;
    use EmphStrongTrait;
    use LinkTrait;
    use StrikeoutTrait;
    use UrlLinkTrait;

    protected $config = [
        'h1' => 'yellow',
        'h2' => 'blue',
        'h3' => 'green',
        'h4' => 'cyan',
        'h5' => 'lightRed',
        'h6' => 'red',
        'link' => 'lightCyan',
        'code' => 'bold',
    ];

    public function __construct()
    {
        $this->su = new StringUtils;
        $this->colorize = new Colorize;
    }

    public function getConfig() : array
    {
        return $this->config;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function hasConfigOption(string $option) : bool
    {
        return isset($this->config[$option]);
    }

    public function getConfigOption(string $option) : string
    {
        return $this->config[$option];
    }

    protected function renderParagraph($block)
    {
        return $this->renderAbsy($block['content']) . "\n";
    }

    protected function renderHeadline($block)
    {
        $color = $this->hasConfigOption('h' . $block['level']) ? $this->getConfigOption('h' . $block['level']) : 'bold';
        return $this->colorize->$color($this->su->fill($block['level'], '-') . $this->renderAbsy($block['content'])) . "\n";
    }
    
    protected function renderInlineCode($block)
    {
        $color = $this->hasConfigOption('code') ? $this->getConfigOption('code') : 'italic';
        return $this->colorize->$color(PHP_EOL . str_replace('```', PHP_EOL, $block[1]) . PHP_EOL);
    }

    protected function renderList($block, $spacing = 0)
    {
        $output = '';
        $type = $block['list'] == 'ul' ? '*' : '#';
        $i = 0;
        foreach ($block['items'] as $item => $itemLines) {
            $i++;
            foreach ($itemLines as $item) {
                switch ($item[0]) {
                    case 'text':
                        if ($type == '#') {
                            $output .= $this->su->fill($spacing) . $i . '. ' . $item[1] . "\n";
                        } else {
                            $output .= $this->su->fill($spacing) . '* ' . $item[1] . "\n";
                        }
                        break;
                    case 'list':
                        $output .= $this->renderList($item, $spacing + 4);
                        break;
                }
            }
        }
        return $output . "\n";
    }

    protected function renderQuote($block, $spacing = 4)
    {
        return $this->su->fill($spacing) . str_replace(PHP_EOL, PHP_EOL . $this->su->fill($spacing), $this->renderAbsy($block['content'])) . "\n";
    }

    protected function renderHr($block)
    {
        return $this->su->fill(80, '-') . PHP_EOL;
    }

    protected function renderStrong($block)
    {
        return $this->colorize->bold($this->renderAbsy($block[1]));
    }

    protected function renderEmph($block)
    {
        return $this->colorize->italic($this->renderAbsy($block[1]));
    }

    protected function renderLink($block)
    {
        if (isset($block['refkey'])) {
            if (($ref = $this->lookupReference($block['refkey'])) !== false) {
                $block = array_merge($block, $ref);
            } else {
                if (strncmp($block['orig'], '[', 1) === 0) {
                    return '[' . $this->renderAbsy($this->parseInline(substr($block['orig'], 1)));
                }
                return $block['orig'];
            }
        }
        $color = $this->hasConfigOption('link') ? $this->getConfigOption('link') : 'bold';
        return $this->colorize->$color($this->renderAbsy($block['text']) . (empty($block['title']) ? '' : ' (' . htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8') . ')') .' => ' . htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8'));
    }

    protected function renderImage($block)
    {
        if (isset($block['refkey'])) {
            if (($ref = $this->lookupReference($block['refkey'])) !== false) {
                $block = array_merge($block, $ref);
            } else {
                if (strncmp($block['orig'], '![', 2) === 0) {
                    return '![' . $this->renderAbsy($this->parseInline(substr($block['orig'], 2)));
                }
                return $block['orig'];
            }
        }
        return $this->colorize->bold(htmlspecialchars($block['text'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8') .
            (empty($block['title']) ? '' : '  (' . htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8') . ')') .
            ' => ' . htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8'));
    }

    protected function renderStrike($block)
    {
        return $this->colorize->strikeout($this->renderAbsy($block[1]));
    }

    protected function renderAutoUrl($block)
    {
        $href = htmlspecialchars($block[1], ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $decodedUrl = urldecode($block[1]);
        $secureUrlText = preg_match('//u', $decodedUrl) ? $decodedUrl : $block[1];
        $text = htmlspecialchars($secureUrlText, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $this->colorize->bold($href);
    }
}
