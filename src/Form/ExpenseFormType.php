<?php

namespace App\Form;

use App\Entity\Expense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpenseFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('expenseDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Select a date'
                ]
            ])

            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Salary' => 'Salary',
                    'Supply' => 'Supply',
                    'Rent' => 'Rent',
                    'Tools' => 'Tools',
                    'Marketing' => 'Marketing',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Choose category'
            ])

            ->add('amount', MoneyType::class, [
                'currency' => false,

                'attr' => [
                    'type' => 'number',
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00'
                ]
            ])

            ->add('description', TextareaType::class, [
                'required' => false,

                'attr' => [
                    'placeholder' => 'Optional description...',
                    'rows' => 1
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expense::class,
        ]);
    }
}