<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        //   public UserPasswordHasherInterface $userPasswordHasher
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('username');
        yield TextField::new('email')->setFormType(EntityType::class)->setFormTypeOptions(['class' => User::class]);
        yield TextField::new('etat_civil', 'Etat Civil');
        yield ArrayField::new('roles', 'Droits');
        // ceck ho to :yield ChoiceField::new('roles', 'Droits');
        // yield TextField::new('participants', 'Participations');

        /* yield TextField::new('password')
             ->setFormType(RepeatedType::class)
             ->setFormTypeOptions([
                 'type' => PasswordType::class,
                 'first_options' => ['label' => 'Password'],
                 'second_options' => ['label' => '(Repeat)'],
                 'mapped' => false,
             ])
             ->setRequired(Crud::PAGE_NEW === $pageName)
             ->onlyOnForms()
         ;*/

        /* $createdAt = DateTimeField::new('createdAt')
             ->setFormTypeOptions([
                 'years' => range(date('Y'), date('Y') | 5),
                 'widget' => 'single_text',
             ]);

         if (Crud::PAGE_EDIT === $pageName) {
             yield $createdAt->setFormTypeOption('disabled', true);
         } else {
             yield $createdAt;
         }
*/
        /* SAMPLE
        yield AssociationField::new('{entity_name}');
        yield TextareaField::new('text')
            ->hideOnIndex()
        ;
        yield TextField::new('photoFilename')
            ->onlyOnIndex()
        ;
        */
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud;
        /*
            ->setEntityLabelInSingular('Conference Comment')
            ->setEntityLabelInPlural('Conference Comments')
            ->setSearchFields(['author', 'text', 'email'])
            ->setDefaultSort(['created_at' => 'DESC'])
            ;*/
    }

    /*
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('email'))
            // ->add(EntityFilter::new('username'))
        //    ->add(EntityFilter::new('id'))
          //  ->add(EntityFilter::new('roles'))
        ;
    }
    */

    /*
     * public function configureActions(Actions $actions): Actions
     * {
     * return $actions
     * ->add(Crud::PAGE_EDIT, Action::INDEX)
     * ->add(Crud::PAGE_INDEX, Action::DETAIL)
     * ->add(Crud::PAGE_EDIT, Action::DETAIL)
     * ;
     * }.
     */
    /*
    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    private function addPasswordEventListener(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    private function hashPassword()
    {
        return function ($event) {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }
            $password = $form->get('password')->getData();
            if (null === $password) {
                return;
            }

            $hash = $this->userPasswordHasher->hashPassword($this->getUser(), $password);
            $form->getData()->setPassword($hash);
        };
    }*/
}
