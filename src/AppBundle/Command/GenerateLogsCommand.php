<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLogsCommand extends ContainerAwareCommand
{
    const DEFAULT_QUANTITY = 1000;

    protected function configure()
    {
        $this
          ->setName('generate-logs')
          ->setDescription('Fill up ObjectLog entitity table with fake data')
          ->addOption('quantity', null, InputOption::VALUE_REQUIRED, 'How many objects to generate', self::DEFAULT_QUANTITY)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);

        $qty = $input->hasOption('quantity') ? (int)$input->getOption('quantity') : self::DEFAULT_QUANTITY;

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /* @var $logRepo \AppBundle\Repository\ObjectsLogRepository */
        $logRepo = $em->getRepository('AppBundle:ObjectsLog');

        $io->comment("Hey, you going to create {$qty} log entries!");

        if ($io->confirm("Do you want to truncate log entries first?")) {
            $logRepo->createQueryBuilder('l')->delete()->getQuery()->execute();
            $io->comment('cleared!');
        }

        $faker = \Faker\Factory::create();
        $faker->addProvider(new \Faker\Provider\Lorem($faker));
        $faker->addProvider(new \Faker\Provider\DateTime($faker));
        $faker->addProvider(new \Faker\Provider\Person($faker));

        $io->progressStart($qty);
        for ($i = 0; $i < $qty; $i++) {
            $log = (new \AppBundle\Entity\ObjectsLog())
              ->setCode($faker->numberBetween(100, 999))
              ->setCreatedAt($faker->dateTimeBetween('-10 years', 'now'))
              ->setData($faker->paragraphs(3, true))
              ->setEmail($faker->email)
              ->setIp($faker->ipv4)
              ->setDeleted($faker->boolean())
              ->setType($faker->randomElement(['bar', 'foo', 'fizz', 'buzz', 'log']));
            $em->persist($log);
            if ($i && $i % 100 == 0) {
                $io->progressAdvance(100);
                $em->flush();
            }
        }
        $io->progressFinish();
        $em->flush();
        $io->comment('done');
    }

}
