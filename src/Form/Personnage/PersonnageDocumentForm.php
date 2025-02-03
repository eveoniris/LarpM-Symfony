<?php


namespace App\Form\Personnage;

use App\Entity\Personnage;
use App\Repository\DocumentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnageDocumentForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('documents', EntityType::class, [
            'label' => 'Choisissez les documents possédés par le personnage en début de jeu',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'class' => \App\Entity\Document::class,
            'choice_label' => 'identity',
            'query_builder' => static function (DocumentRepository $er) {
                return $er->createQueryBuilder('d')->orderBy('d.code', 'ASC');
            },
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
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
        return 'personnageDocument';
    }
}
