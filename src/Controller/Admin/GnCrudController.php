<?php

namespace App\Controller\Admin;

use App\Entity\Gn;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class GnCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Gn::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('label');
        yield TextEditorField::new('description');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('label'));
    }
}
