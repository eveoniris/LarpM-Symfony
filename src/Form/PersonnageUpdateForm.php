<?php

namespace App\Form;

use App\Entity\Age;
use App\Entity\Genre;
use App\Entity\Personnage;
use App\Entity\Territoire;
use App\Enum\Role;
use App\Repository\TerritoireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonnageUpdateForm extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->security->isGranted(Role::SCENARISTE->value) || $this->security->isGranted(Role::ORGA->value)) {
            $builder->add('nom', TextType::class, [
                'required' => true,
                'label' => '',
            ])
                ->add('age', EntityType::class, [
                    'required' => true,
                    'label' => '',
                    'class' => Age::class,
                    'choice_label' => 'label',
                ])
                ->add('genre', EntityType::class, [
                    'required' => true,
                    'label' => '',
                    'class' => Genre::class,
                    'choice_label' => 'label',
                ])
                ->add('territoire', EntityType::class, [
                    'required' => true,
                    'label' => 'Origine du personnage',
                    'class' => Territoire::class,
                    'choice_label' => 'nom',
                    'query_builder' => static function (TerritoireRepository $er) {
                        $qb = $er->createQueryBuilder('t');
                        $qb->andWhere('t.territoire IS NULL');

                        return $qb;
                    },
                ]);
        }

        /** @var Personnage $personnage */
        $personnage = $builder->getData();
        $braceletAttr = [];
        $braceletIcon = '';
        if (false === $personnage->isBraceletSetted()) {
            $braceletAttr = ['class' => 'text-danger'];
            $braceletIcon = '<i class="fa-solid fa-circle-exclamation me-1"></i>';
        }

        $builder->add('surnom', TextType::class, [
            'required' => false,
            'label' => '',
        ])
            ->add('intrigue', ChoiceType::class, [
                'required' => true,
                'choices' => ['Oui' => true, 'Non' => false],
                'label' => 'Participer aux intrigues (aide de camps)',
            ])
            ->add('sensible', ChoiceType::class, [
                'required' => true,
                'choices' => ['Non' => false, 'Oui' => true],
                'label' => 'Personnage sensible',
            ])
            ->add('bracelet', ChoiceType::class, [
                'required' => true,
                'choices' => ['Non' => false, 'Oui' => true],
                'label_attr' => $braceletAttr,
                'label_html' => true,
                'label' => $braceletIcon.'Possédez-vous votre bracelet de langue ?',
                'help' => 'Si vous cochez Oui. Vous ne recevrez pas de bracelet de langue dans votre enveloppe personnage ',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageUpdate';
    }
}
