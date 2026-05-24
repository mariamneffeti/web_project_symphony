<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'First name'],
                'constraints' => [new NotBlank()],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Last name'],
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['class' => 'form-control'],
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'required'        => false,
                'first_options'   => [
                    'label' => 'New Password',
                    'attr'  => ['class' => 'form-control', 'placeholder' => '••••••••'],
                    'constraints' => [
                        new Length([
                            'min'        => 6,
                            'minMessage' => 'Password must be at least 6 characters.',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr'  => ['class' => 'form-control', 'placeholder' => '••••••••'],
                ],
                'invalid_message' => 'Passwords do not match.',
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Profile Image',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/webp',
                    'id'     => 'imageFileInput',
                ],
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP).',
                        'maxSizeMessage'   => 'Image too large (max 2MB).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => User::class,
            'csrf_protection' => false,
        ]);
    }
}
