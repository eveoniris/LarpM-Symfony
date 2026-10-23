<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Enum\EtiquetteCouleur;
use App\Enum\EtiquetteNature;
use App\Enum\EtiquetteSituation;
use App\Service\EtiquetteSaisie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class DepouillementEtiquetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recherche', TextType::class, [
                'label' => 'Chercher un objet par son nom ou son numéro',
                'mapped' => false,
                'required' => false,
                'help' => 'Facultatif : choisissez l\'objet dans la liste, le numéro, la qualité et la couleur se remplissent. Un objet de l\'association peut aussi être une bannière.',
                'attr' => ['autocomplete' => 'off', 'list' => 'lm-objets-suggestions', 'data-lm-objet-recherche' => $options['recherche_url'], 'placeholder' => 'Ex. bannière, 256...'],
            ])
            ->add('nature', EnumType::class, [
                'label' => 'Type d\'étiquette',
                'class' => EtiquetteNature::class,
                'choice_label' => static fn (EtiquetteNature $nature): string => $nature->label(),
                'expanded' => true,
                'help' => 'Une étiquette d\'arme est produite en masse (son numéro est un numéro de stock ou une feinte). Une étiquette d\'objet désigne un objet connu.',
            ])
            ->add('couleur', EnumType::class, [
                'label' => 'Couleur',
                'class' => EtiquetteCouleur::class,
                'choice_label' => static fn (EtiquetteCouleur $couleur): string => $couleur->label(),
                'expanded' => true,
            ])
            ->add('numero', IntegerType::class, [
                'label' => 'Numéro (en haut à gauche)',
                'required' => false,
                'attr' => ['inputmode' => 'numeric', 'min' => 1],
            ])
            ->add('qualite', TextType::class, [
                'label' => 'Qualité (en haut à droite, 3 chiffres)',
                'required' => false,
                'constraints' => [new Regex(pattern: '/^\d{3}$/', message: 'La qualité a exactement trois chiffres.')],
                'attr' => ['inputmode' => 'numeric', 'maxlength' => 3, 'pattern' => '\d{3}'],
                'help' => 'Laisser vide pour un lot d\'étiquettes d\'armes génériques comptées en nombre.',
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Nombre d\'étiquettes (armes génériques)',
                'attr' => ['inputmode' => 'numeric', 'min' => 1, 'max' => 999],
            ])
            ->add('situation', EnumType::class, [
                'label' => 'Constat',
                'class' => EtiquetteSituation::class,
                'choice_label' => static fn (EtiquetteSituation $situation): string => $situation->label(),
            ])
            ->add('remarque', TextType::class, [
                'label' => 'Remarque',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, ['label' => 'Ajouter l\'étiquette']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => EtiquetteSaisie::class, 'recherche_url' => '']);
        $resolver->setAllowedTypes('recherche_url', 'string');
    }
}
