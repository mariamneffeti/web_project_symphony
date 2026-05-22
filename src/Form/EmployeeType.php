<?php

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Name',
                'attr' => ['placeholder' => 'Employee name', 'class' => 'form-control'],
                'constraints' => [new NotBlank()],
            ])
            ->add('department', ChoiceType::class, [
                'label' => 'Department',
                'choices' => [
                    'Choose...'       => '',
                    'IT'              => 'IT',
                    'Finance'         => 'Finance',
                    'Marketing'       => 'Marketing',
                    'Human Resources' => 'Human Resources',
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [new NotBlank()],
            ])
            ->add('position', TextType::class, [
                'label' => 'Position',
                'required' => false,
                'attr' => ['placeholder' => 'Position', 'class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'email@company.com', 'class' => 'form-control'],
                'constraints' => [new NotBlank(), new Email()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Employee::class]);
    }
}
