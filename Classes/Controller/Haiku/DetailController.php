<?php

namespace T3docs\Examples\Controller\Haiku;

use Psr\Http\Message\ServerRequestInterface;
use T3docs\Examples\Domain\Repository\HaikuRepository;
use T3docs\Examples\Service\FlexFormSettingsService;
use T3docs\Examples\Service\StandaloneViewService;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class DetailController
{
    /**
     * The back-reference to the mother cObj object set at call time
     */
    public ContentObjectRenderer $cObj;
    public array $conf = [];
    public StandaloneView $view;

    public function __construct(
        private readonly HaikuRepository $haikuRepository,
        private readonly FlexFormSettingsService $flexFormSettingsService,
        private readonly StandaloneViewService $viewService,
    ) {
    }

    /**
     * This setter is called when the plugin is called from UserContentObject (USER)
     * via ContentObjectRenderer->callUserFunction().
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * @throws PropagateResponseException
     */
    public function main(string $content, array $conf, ServerRequestInterface $request): string
    {
        $this->conf = $conf;
        $this->loadFlexFormSettings();
        $this->view = $this->viewService->createView($request, $this->conf, 'Haiku/Detail');

        $parameter = $request->getQueryParams()['tx_examples_haiku']??[];
        $action = $parameter['action'] ?? '';
        try {
            $result = match ($action) {
                'show' => $this->showAction((int)($parameter['haiku'] ?? 0)),
                'findByTitle' => $this->findByTitleAction((string)($parameter['haiku_title'] ?? '')),
                default => $this->notFoundAction('Action ' . $action . ' not found.'),
            };
        } catch (\Exception $e) {
            $this->notFoundAction($e->getMessage());
        }
        return $result;
    }

    /**
     * @throws PropagateResponseException
     */
    private function notFoundAction(string $reason)
    {
        throw new PropagateResponseException(
            new Response(
                null,
                404,
                [],
                $reason
            )
        );
    }

    private function showAction(int $haikuId): string
    {
        $this->view->assignMultiple([
            'haiku' => $this->haikuRepository->findByUid($haikuId),
        ]);
        return $this->view->render();
    }

    private function findByTitleAction(string $haikuTitle): string
    {
        $this->view->assignMultiple([
            'haiku' => $this->haikuRepository->findByTitle($haikuTitle),
        ]);
        return $this->view->render();
    }

    private function loadFlexFormSettings(): void
    {
        $this->conf['settings'] = $this->flexFormSettingsService->combineSettings(
            $this->conf['settings'] ?? [],
            $this->cObj->data['pi_flexform'] ?? ''
        );
    }
}