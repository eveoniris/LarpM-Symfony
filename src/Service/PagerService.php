<?php

namespace App\Service;

use App\Form\Entity\ListSearch;
use App\Form\ListFindForm;
use App\Repository\BaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PagerService
{
    protected int $limit;
    protected int $page;
    protected FormInterface $form;
    protected mixed $searchValue;
    protected string|array|null $searchType;
    protected Request $request;
    protected ?BaseRepository $repository = null;

    public function __construct(
        protected readonly RequestStack $requestStack,
        protected readonly OrderBy $orderBy,
        protected readonly FormFactoryInterface $formFactory,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UrlGeneratorInterface $urlGenerator,
    ) {
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

    public function setLimit(int $limit): PagerService
    {
        $this->limit = $limit;

        return $this;
    }

    public function getRequest(): ?Request
    {
        if (isset($this->request)) {
            return $this->request;
        }

        $this->request = $this->requestStack->getCurrentRequest();

        return $this->request;
    }

    public function setRequest(Request $request): PagerService
    {
        $this->request = $request;
        $this->orderBy->getFromRequest($request); // reload

        return $this;
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

    /**
     * Return all orders as a string for link.
     * First link will order by ASC
     * Second will order by DESC
     * Third will not order by.
     */
    public function getSearchOrderPathLinkForField(string $field, ?string $route = null, ?array $params = null): string
    {
        $request = $this->getRequest();

        $route ??= $request?->attributes->get('_route') ?? 'homepage';
        $params ??= $request?->attributes->get('_route_params') ?? [];
        $params = [...$params, ...($request?->query->all() ?? [])];
        $params['q'] = $this->getSearchValue();
        $params['t'] = $this->getSearchType();

        $orders = $this->getOrderBy()->getOrders();

        $orders[$field] = $this->getOrderBy()->getNextDirForLink($orders[$field] ?? null);

        if (null === $orders[$field]) {
            unset($orders[$field]);
        }

        $s = '';
        foreach ($orders as $by => $sort) {
            $s .= (OrderBy::ASC === $sort || '' === $sort) ? '' : '-';
            $s .= $by.',';
        }

        $params['order_by'] = trim($s, ',');

        return $this->urlGenerator->generate($route, $params);
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

    protected function loadForm(): void
    {
        $form = $this->getForm();

        if (!new ($form->getConfig()->getDataClass())() instanceof ListSearch) {
            return;
        }

        // handle Get search
        $this->searchValue = $this->getRequest()?->get('search')
            ?? $this->getRequest()?->get('q');
        $this->searchType = $this->getRequest()?->get('searchType')
            ?? $this->getRequest()?->get('t');

        /** @var $data ListSearch */
        if ($form->isSubmitted() && $form->isValid() && $data = $form->getData()) {
            $this->searchType = $data->getType();
            $this->searchValue = $data->getValue();
        }
    }

    public function getForm(?string $type = null, ?ListSearch $data = null, array $options = []): FormInterface
    {
        if (isset($this->form)) {
            return $this->form;
        }

        // If we set a DataClass that's extends ListSearch (Entity doesn't have a BaseEntity)
        if (!$data && $this->getRepository()) {
            $entity = new ($this->getRepository()->getClassName())();
            if ($entity instanceof ListSearch || is_subclass_of($entity::class, ListSearch::class)) {
                $data = $entity;
            }
        }
        $data ??= new ListSearch();
        if (empty($options)) {
            $typeChoicesOverride = $data->getTypeChoices();

            if (empty($typeChoicesOverride) && $this->getRepository()) {
                $typeChoicesOverride = [
                    'type_choices' => [
                        'required' => true,
                        'choices' => [],
                    ],
                ];

                foreach ($this->getRepository()->searchAttributes() as $searchKey => $searchAttribute) {
                    $attribute = $searchKey;
                    $label = $searchAttribute;
                    if (is_int($searchKey)) {
                        $label = $this->getRepository()->translateAttribute($searchAttribute);
                        $attribute = $searchAttribute;
                    }

                    $typeChoicesOverride['type_choices']['choices'][$label] = $this->getRepository(
                    )->getAttributeWhereName($attribute);
                }
            }

            $options = $typeChoicesOverride;
        }

        // may from GET
        if (empty($data->getValue()) && $search = $this->getRequest()?->get('search') ?? $this->getRequest()?->get(
                'q',
            )) {
            $data->setValue($search);
            $this->searchValue = $search;
        }

        if (empty($data->getType()) && $type = $this->getRequest()?->get('searchType') ?? $this->getRequest()?->get(
                't',
            )) {
            $data->setType($type);
            $this->searchType = $type;
        }

        $this->form = $this->formFactory->create(
            type: $type ?? ListFindForm::class,
            data: $data,
            options: $options,
        );

        if ($request = $this->getRequest()) {
            $this->form->handleRequest($request);
        }

        return $this->form;
    }

    public function getRepository(): ?BaseRepository
    {
        return $this->repository;
    }

    public function setRepository(BaseRepository $repository): PagerService
    {
        $this->repository = $repository;

        return $this;
    }

    public function getSearchType(): string|array|null
    {
        if (isset($this->searchType)) {
            return $this->searchType;
        }

        $this->searchType = null;
        $this->loadForm();

        return $this->searchType;
    }

    public function getOrderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function isAsc(string $field): bool
    {
        $dir = $this->getOrderBy()->getOrders()[$field] ?? OrderBy::ASC;

        return !('-' === $dir || OrderBy::DESC === $dir);
    }

    public function isInOrder(string $field): bool
    {
        return $this->orderBy->getOrders()[$field] ?? false;
    }

    public function setDefaultOrderBy(string $orderBy): PagerService
    {
        $this->orderBy->setDefaultOrderDir($orderBy);

        return $this;
    }

    public function setDefaultOrdersBy(array $orders): PagerService
    {
        if (!empty($this->orderBy->getOrders())) {
            return $this;
        }

        $this->orderBy->setOrders($orders);

        return $this;
    }

    public function setOrdersBy(array $orders): PagerService
    {
        $this->orderBy->setOrders($orders);

        return $this;
    }
}
