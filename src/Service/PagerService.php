<?php

namespace App\Service;

use App\Form\Entity\ListSearch;
use App\Form\ListFindForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class PagerService
{
    protected int $limit;
    protected int $page;
    protected FormInterface $form;
    protected mixed $searchValue;
    protected null|string|array $searchType;
    protected Request $request;

    public function __construct(
        protected RequestStack $requestStack,
        protected OrderBy $orderBy,
        protected FormFactoryInterface $formFactory,
    ) {
    }

    public function getForm(string $type = null, ListSearch $data = null, array $options = []): FormInterface
    {
        if (isset($this->form)) {
            return $this->form;
        }

        $data ??= $data ?? new ListSearch();

        // may from GET
        if (empty($data->getValue()) && $search = $this->getRequest()?->get('search')) {
            $data->setValue($search);
            $this->searchValue = $search;
        }

        $this->form = $this->formFactory->create(
            type: $type ?? ListFindForm::class,
            data: $data,
            options: $options
        );

        if ($request = $this->getRequest()) {
            $this->form->handleRequest($request);
        }

        return $this->form;
    }

    public function getSearchValue(): mixed
    {
        if (isset($this->searchValue)) {
            return $this->searchValue;
        }

        $this->searchValue = null;
        $this->loadForm();

        return $this->searchValue;
    }

    public function getSearchType(): null|string|array
    {
        if (isset($this->searchType)) {
            return $this->searchType;
        }

        $this->searchType = null;
        $this->loadForm();

        return $this->searchType;
    }

    protected function loadForm(): void
    {
        $form = $this->getForm();

        if (!new ($form->getConfig()->getDataClass()) instanceof ListSearch) {
            return;
        }

        // handle Get search

        /** @var $data ListSearch */
        if ($form->isSubmitted() && $form->isValid() && $data = $form->getData()) {
            $this->searchType = $data->getType();
            $this->searchValue = $data->getValue() ?? $this->getRequest()?->get('search');
        }
    }

    public function getLimit(int $defLimit = 10): int
    {
        if (isset($this->limit)) {
            return $this->limit;
        }

        $request = $this->getRequest();

        if (!$request) {
            $this->limit = $defLimit;

            return $this->limit;
        }

        $limit = $request->query->getInt('limit', $defLimit);

        $this->limit = $defLimit;
        if (!is_numeric($limit)) {
            return $this->limit;
        }

        // We limit between 1 and 100 if not from code
        $max = max($defLimit, 100);
        $this->limit = min($max, max(1, $this->limit));

        return $this->limit;
    }

    public function getPage(int $defPage = 1): int
    {
        if (isset($this->page)) {
            return $this->page;
        }

        $defPage = max(1, $defPage);

        $request = $this->getRequest();

        if (!$request) {
            $this->page = $defPage;

            return $this->page;
        }

        $page = $request->query->getInt('page', $defPage);

        if (!is_numeric($page)) {
            $this->page = $defPage;

            return $this->page;
        }

        // Page can be lower than 1
        $this->page = max(1, $page);

        return $this->page;
    }

    public function getOrderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function setRequest(Request $request): PagerService
    {
        $this->request = $request;

        return $this;
    }

    protected function getRequest(): ?Request
    {
        if (isset($this->request)) {
            return $this->request;
        }

        $this->request = $this->requestStack?->getCurrentRequest();

        return $this->request;
    }
}
