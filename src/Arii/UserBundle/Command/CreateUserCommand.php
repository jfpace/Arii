<?php
namespace Arii\UserBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FOS\UserBundle\Command\CreateUserCommand as BaseCommand;

class CreateUserCommand extends BaseCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('arii:user:create')
            ->getDefinition()->addArguments(array(
                new InputArgument('firstname', InputArgument::REQUIRED, 'The firstname'),
                new InputArgument('lastname', InputArgument::REQUIRED, 'The lastname'),
                new InputArgument('enterprise', InputArgument::REQUIRED, 'Enterprise')
            ))
        ;
        $this->setHelp(<<<EOT
// L'aide qui va bien
EOT
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        if (!$input->getArgument('firstname')) {
            $firstname = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a firstname:',
                function($firstname) {
                    if (empty($firstname)) {
                        throw new \Exception('Firstname can not be empty');
                    }

                    return $firstname;
                }
            );
            $input->setArgument('firstname', $firstname);
        }
        if (!$input->getArgument('lastname')) {
            $lastname = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a lastname:',
                function($lastname) {
                    if (empty($lastname)) {
                        throw new \Exception('Lastname can not be empty');
                    }

                    return $lastname;
                }
            );
            $input->setArgument('lastname', $lastname);
        }
        if (!$input->getArgument('enterprise')) {
            $enterprise = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an enterprise:',
                function($enterprise) {
                    if (empty($enterprise)) {
                        throw new \Exception('Enterprise can not be empty');
                    }

                    return $enterprise;
                }
            );
            $input->setArgument('enterprise', $enterprise);
        }
    }
    
    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username   = $input->getArgument('username');
        
        /** @var \FOS\UserBundle\Model\UserManager $user_manager */
        $user_manager = $this->getContainer()->get('fos_user.user_manager');

        /** @var \Arii\UserBundle\Entity\User $user */
        $user = $user_manager->createUser();
        $user->setUsername($input->getArgument('username'));
        $user->setEmail($input->getArgument('email'));
        $user->setPlainPassword($input->getArgument('password'));
        $user->setEnabled(!$input->getOption('inactive'));
        $user->setSuperAdmin((bool)$input->getOption('super-admin'));
        $user->setFirstName($input->getArgument('firstname'));
        $user->setLastName($input->getArgument('lastname'));
        //$user->setEnterprise($input->getArgument('enterprise'));

        $user_manager->updateUser($user);

        $output->writeln(sprintf('Created user <comment>%s</comment>', $username));
    }
    
}

