<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CompetenceNiveauForm extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('competence','entity', array(
					'required' => true,
					'class' => 'App\Entity\Competence',
					'property' => 'nom',
				))
				->add('niveau','entity', array(
					'required' => true,
					'class' => 'App\Entity\Niveau',
					'property' => 'label',
				))
				->add('description','textarea', array(
					'required' => false,		
				));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
				'data' => 'App\Entity\CompetenceNiveau',
		));
	}

	public function getName()
	{
		return 'competenceNiveauForm';
	}
}