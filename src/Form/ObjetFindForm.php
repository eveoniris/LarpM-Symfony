<?php

namespace App\Form;

use App\Entity\Objet;
use App\Entity\Rangement;
use App\Entity\Tag;
use App\Repository\ObjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjetFindForm extends AbstractType
{
    protected EntityManagerInterface $entityManager;
    protected EntityRepository $tagRepository;
    protected EntityRepository $rangementRepository;
    protected EntityRepository $objetRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->rangementRepository = $entityManager->getRepository(Rangement::class);
        $this->tagRepository = $entityManager->getRepository(Tag::class);
        $this->objetRepository = $entityManager->getRepository(Objet::class);
    }

    /**
     * Construction du formulaire.
     *
     * If option "All" is selected then $form->getData() is equal to null
     * If option "None" is selected then $form->getData() is equal to 0
     * If option "Rangement A" is selected then $form->getData() is an instance of Rangement
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => false,
            'label' => 'Recherche',
        ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    '*',
                    'id',
                    'nom',
                    'description',
                    'numero',
                    'i.label', // JOIN item
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    '*' => 'Tout critère',
                    'nom' => 'Nom',
                    'description' => 'Description',
                    'numero' => 'Numero de stock',
                    'i.label' => 'Numero de jeu', // JOIN item
                    'id' => 'ID',
                },
            ])
            ->add('tag', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Tous les tags',
                'choices' => array_merge(
                    [(new Tag())->setNom(
                        sprintf(
                            'Objets sans tag (%d)',
                            $this->objetRepository->findCount(['tag' => ObjetRepository::CRIT_WITHOUT])
                        )
                    )],
                    $this->tagRepository->findAll()
                ),
                'choice_label' => 'nom',
            ])
            ->add('rangement', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Tous les rangements',
                'choices' => array_merge(
                    [(new Rangement())->setLabel(
                        sprintf(
                            'Objets sans rangement (%d)',
                            $this->objetRepository->findCount(['rangement' => ObjetRepository::CRIT_WITHOUT])
                        )
                    )],
                    $this->rangementRepository->findAll()
                ),
                'choice_label' => 'label',
            ])
        ;
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'objetFind';
    }
}
