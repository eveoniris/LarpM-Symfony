<?php


namespace App\Form\Technologie;

use App\Entity\CompetenceFamily;
use App\Entity\Technologie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\TechnologieForm.
 *
 * @author kevin
 */
class TechnologieForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'label' => 'Nom',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description succinte',
            ])
            ->add('competenceFamily', EntityType::class, [
                'class' => CompetenceFamily::class,
                'required' => true,
                'label' => 'Compétence Expert requise',
                'property' => 'label',
            ])
            ->add('document', 'file', [
                'label' => 'Téléversez un document',
                'required' => true,
                'mapped' => false,
            ])
            ->add('secret', CheckboxType::class, [
                'label' => 'Technologie secrète ?',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Technologie::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'technologie';
    }
}
