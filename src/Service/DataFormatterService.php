<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataFormatterService
{
    private array $decorators = [];
    private string $indentChar = '    '; // 4 espaces par défaut

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly FormFactoryInterface $formFactory,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly CompetenceService $competenceService,
        protected readonly GroupeService $groupeService,
        protected readonly Security $security,
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function addDecorator(string $key, callable $decorator): self
    {
        $this->decorators[$key] = $decorator;

        return $this;
    }

    public function format(array $data, int $level = 0): string
    {
        $output = '';
        $indent = str_repeat($this->indentChar, $level);

        foreach ($data as $key => $value) {
            $output .= $indent;

            $codeKey = $key;
            $displayKey = $key;
            if (str_ends_with($codeKey, '_id')) {
                $codeKey = str_replace('_id', '', $codeKey);
                $displayKey = $codeKey;

                $entityKey = match($codeKey) {
                    'enseignant', 'scenariste' => 'user',
                    default => $codeKey,
                };

                // Ajout du décorateur spécial
                if (!isset($this->decorators[$key])) {
                    try {
                        $repo = $this->entityManager->getRepository((new ('App\Entity\\'.ucfirst($entityKey))())::class);
                    } catch (\Exception $e) {
                        $repo = null;
                        $this->logger->info($e);
                    }

                    try {
                        $this->decorators[$key] = function ($value) use ($codeKey, $repo) {
                            $prettyValue = $value;
                            if ($repo && $entity = $repo->findOneBy(['id' => $value])) {
                                if (method_exists($entity, 'getLabel')) {
                                    $prettyValue = $entity->getLabel();
                                } elseif (method_exists($entity, 'getPrintLabel')) {
                                    $prettyValue = $entity->getPrintLabel();
                                } elseif (method_exists($entity, 'getNom')) {
                                    $prettyValue = $entity->getNom();
                                } elseif (method_exists($entity, 'getTitre')) {
                                    $prettyValue = $entity->getTitre();
                                }
                            }

                            try {

                                $string = sprintf(
                                    '<a href="%s" id="entity_%s">%s</a>',
                                    $this->urlGenerator->generate($codeKey.'.detail', [$codeKey => $value]),
                                    $value,
                                    $prettyValue,
                                );
                            } catch (\Exception $e) {
                                $this->logger->warning($e);
                                $string = $prettyValue;
                            }

                            return $string;
                        };
                    } catch (\Exception $e) {
                        $this->logger->warning($e);
                    }
                }
            }

            // Formatage de la clé
            $output .= $displayKey.': ';

            // Application du décorateur si disponible
            if (isset($this->decorators[$key]) && !is_array($value)) {
                $output .= ($this->decorators[$key])($value);
            } // Traitement récursif pour les tableaux
            elseif (is_array($value)) {
                $output .= "\n".$this->format($value, $level + 1);
            } // Valeur simple
            else {
                $output .= $this->formatValue($value);
            }

            $output .= "\n";
        }

        return rtrim($output);
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_string($value)) {
            return '"'.$value.'"';
        }

        return (string) $value;
    }
}
