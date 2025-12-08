<?php

declare(strict_types=1);

namespace MarekSkopal\FontAwesome\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\Event\BeforeStylesheetsRenderingEvent;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

#[AsEventListener]
final readonly class FontAwesomeEventListener
{
    public function __construct(private PageRenderer $pageRenderer)
    {
    }

    public function __invoke(BeforeStylesheetsRenderingEvent $event): void
    {
        $settings = $this->getSettings();
        if ($settings === null) {
            return;
        }

        foreach ($settings['fontSrc.'] as $key => $fontSrc) {
            $this->pageRenderer->addHeaderData($this->getLinkStylesheet($fontSrc, $key));
        }
    }

    private function getLinkStylesheet(string $fontSrc, string|int $key): string
    {
        $id = 'fontawesomeCss' . $key;

        $linkAttributes = [
            'id' => $id,
            'rel' => 'stylesheet',
            'href' => $fontSrc,
            'media' => 'print',
            'nonce' => $this->getCspNonce(),
        ];

        $tag = '<link ' . $this->getTagAttributes($linkAttributes) . '>';

        $scriptAttributes = [
            'nonce' => $this->getCspNonce(),
        ];

        return $tag . '<script ' . $this->getTagAttributes(
            $scriptAttributes,
        ) . '>' . $id . '.addEventListener(\'load\', function(){this.media="all"})</script>';
    }

    /** @param array<string, string|null> $attributes */
    private function getTagAttributes(array $attributes): string
    {
        $tagAttributes = array_filter($attributes, fn(?string $value): bool => $value !== null);

        return implode(' ', array_map(
            fn(string $value, string $key): string => $key . '="' . $value . '"',
            array_values($tagAttributes),
            array_keys($tagAttributes),
        ));
    }

    private function getCspNonce(): ?string
    {
        /** @var ConsumableNonce|null $nonce */
        $nonce = $this->getRequest()->getAttribute('nonce');
        return $nonce instanceof ConsumableNonce ? $nonce->consumeInline(Directive::ScriptSrcElem) : null;
    }

    /** @return array{"fontSrc.": array<int, string>}|null */
    private function getSettings(): ?array
    {
        $setupArray = $this->getFrontendTypoScript()?->getSetupArray();
        if ($setupArray === null) {
            return null;
        }

        //@phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
        return $setupArray['plugin.']['tx_msfontawesome.']['settings.'] ?? null;
    }

    private function getFrontendTypoScript(): ?FrontendTypoScript
    {
        /** @var FrontendTypoScript|null $frontendTypoScript */
        $frontendTypoScript = $this->getRequest()->getAttribute('frontend.typoscript');
        return $frontendTypoScript;
    }

    private function getRequest(): ServerRequestInterface
    {
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        return $request;
    }
}
