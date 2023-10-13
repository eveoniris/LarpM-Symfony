<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CompetenceMinimalForm extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('nom','text', array(
					'required' => true,	)
				)
				->add('description','textarea', array(
					'required' => false,	)
				);
	}
	
	public function getName()
	{
		return 'competenceMinimalForm';
	}
}