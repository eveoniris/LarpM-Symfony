<?php

namespace App\Command;

use App\Entity\Competence;
use App\Entity\Level;
use App\Entity\Personnage;
use App\Entity\PersonnageApprentissage;
use App\Service\CompetenceService;
use App\Service\PersonnageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gn-give-learning',
    description: 'Attribue les apprentissages retour de jeu',
)]
class GnGiveLearning extends Command
{
    public function __construct(protected readonly EntityManagerInterface $entityManager, private readonly PersonnageService $personnageService, private readonly CompetenceService $competenceService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('gn', InputArgument::OPTIONAL, 'GN id if not the next session', default: null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Gestion des apprentissages en retour du GN');

        $datas = [
            ['personnage' => 5040, 'enseignant' => 25, 'competence' => 7],
            ['personnage' => 4405, 'enseignant' => 618, 'competence' => 7],
        ['personnage' => 794, 'enseignant' => 3013, 'competence' => 7],
['personnage' => 5660, 'enseignant' => 1319, 'competence' => 7],
['personnage' => 4927, 'enseignant' => 3878, 'competence' => 7],
['personnage' => 4778, 'enseignant' => 1623, 'competence' => 7],
['personnage' => 466, 'enseignant' => 4927, 'competence' => 7],
['personnage' => 5515, 'enseignant' => 4531, 'competence' => 7],
['personnage' => 5082, 'enseignant' => 3363, 'competence' => 112],
['personnage' => 5072, 'enseignant' => 1118, 'competence' => 23],
['personnage' => 5114, 'enseignant' => 2080, 'competence' => 23],
['personnage' => 4204, 'enseignant' => 4909, 'competence' => 12],
['personnage' => 5081, 'enseignant' => 5129, 'competence' => 12],
['personnage' => 2794, 'enseignant' => 2764, 'competence' => 12],
['personnage' => 3246, 'enseignant' => 3978, 'competence' => 18],
['personnage' => 4127, 'enseignant' => 245, 'competence' => 18],
['personnage' => 5023, 'enseignant' => 5441, 'competence' => 23],
['personnage' => 4705, 'enseignant' => 1118, 'competence' => 26],
['personnage' => 5208, 'enseignant' => 245, 'competence' => 12],
['personnage' => 4433, 'enseignant' => 4273, 'competence' => 12],
['personnage' => 3062, 'enseignant' => 4527, 'competence' => 12],
['personnage' => 3979, 'enseignant' => 4527, 'competence' => 12],
['personnage' => 5295, 'enseignant' => 245, 'competence' => 12],
['personnage' => 3231, 'enseignant' => 5267, 'competence' => 28],
['personnage' => 1169, 'enseignant' => 1139, 'competence' => 28],
['personnage' => 1061, 'enseignant' => 1139, 'competence' => 28],
['personnage' => 3985, 'enseignant' => 1139, 'competence' => 28],
['personnage' => 5493, 'enseignant' => 2289, 'competence' => 12],
['personnage' => 3956, 'enseignant' => 2080, 'competence' => 23],
['personnage' => 5621, 'enseignant' => 2557, 'competence' => 23],
['personnage' => 3156, 'enseignant' => 2829, 'competence' => 23],
['personnage' => 4081, 'enseignant' => 2829, 'competence' => 23],
['personnage' => 5558, 'enseignant' => 5176, 'competence' => 23],
['personnage' => 4957, 'enseignant' => 2080, 'competence' => 23],
['personnage' => 3601, 'enseignant' => 3609, 'competence' => 33],
['personnage' => 5191, 'enseignant' => 4964, 'competence' => 33],
['personnage' => 5699, 'enseignant' => 1889, 'competence' => 33],
['personnage' => 5300, 'enseignant' => 3601, 'competence' => 33],
['personnage' => 4706, 'enseignant' => 2949, 'competence' => 33],
['personnage' => 4922, 'enseignant' => 4617, 'competence' => 43],
['personnage' => 5661, 'enseignant' => 5107, 'competence' => 43],
['personnage' => 478, 'enseignant' => 3331, 'competence' => 43],
['personnage' => 4013, 'enseignant' => 3331, 'competence' => 43],
['personnage' => 4836, 'enseignant' => 2842, 'competence' => 48],
['personnage' => 3952, 'enseignant' => 4052, 'competence' => 48],
['personnage' => 4948, 'enseignant' => 5183, 'competence' => 53],
['personnage' => 5597, 'enseignant' => 2978, 'competence' => 53],
['personnage' => 5192, 'enseignant' => 1312, 'competence' => 53],
['personnage' => 3899, 'enseignant' => 2043, 'competence' => 53],
['personnage' => 5025, 'enseignant' => 1585, 'competence' => 53],
['personnage' => 5355, 'enseignant' => 2043, 'competence' => 53],
['personnage' => 5172, 'enseignant' => 2043, 'competence' => 53],
['personnage' => 5172, 'enseignant' => 2043, 'competence' => 53],
['personnage' => 5172, 'enseignant' => 2043, 'competence' => 53],
['personnage' => 3157, 'enseignant' => 2202, 'competence' => 53],
['personnage' => 4450, 'enseignant' => 5137, 'competence' => 53],
['personnage' => 5252, 'enseignant' => 5007, 'competence' => 58],
['personnage' => 5555, 'enseignant' => 5075, 'competence' => 58],
['personnage' => 3951, 'enseignant' => 769, 'competence' => 58],
['personnage' => 5079, 'enseignant' => 3136, 'competence' => 58],
['personnage' => 5644, 'enseignant' => 5075, 'competence' => 58],
['personnage' => 3959, 'enseignant' => 5605, 'competence' => 58],
['personnage' => 5085, 'enseignant' => 2868, 'competence' => 58],
['personnage' => 4697, 'enseignant' => 5605, 'competence' => 58],
['personnage' => 5425, 'enseignant' => 3136, 'competence' => 58],
['personnage' => 3532, 'enseignant' => 3532, 'competence' => 58],
['personnage' => 5767, 'enseignant' => 3136, 'competence' => 58],
['personnage' => 5194, 'enseignant' => 1, 'competence' => 58],
['personnage' => 3274, 'enseignant' => 3136, 'competence' => 58],
['personnage' => 5194, 'enseignant' => 4792, 'competence' => 58],
['personnage' => 5642, 'enseignant' => 4792, 'competence' => 58],
['personnage' => 5153, 'enseignant' => 2479, 'competence' => 137],
['personnage' => 5107, 'enseignant' => 3307, 'competence' => 137],
['personnage' => 4052, 'enseignant' => 5011, 'competence' => 137],
['personnage' => 5628, 'enseignant' => 3386, 'competence' => 137],
['personnage' => 4234, 'enseignant' => 2001, 'competence' => 63],
['personnage' => 4175, 'enseignant' => 2255, 'competence' => 63],
['personnage' => 5564, 'enseignant' => 4399, 'competence' => 63],
['personnage' => 5188, 'enseignant' => 291, 'competence' => 63],
['personnage' => 5117, 'enseignant' => 479, 'competence' => 63],
['personnage' => 4075, 'enseignant' => 2401, 'competence' => 63],
['personnage' => 5075, 'enseignant' => 2927, 'competence' => 63],
['personnage' => 4175, 'enseignant' => 2255, 'competence' => 63],
['personnage' => 5019, 'enseignant' => 4778, 'competence' => 63],
['personnage' => 5095, 'enseignant' => 2927, 'competence' => 63],
['personnage' => 5291, 'enseignant' => 4603, 'competence' => 63],
['personnage' => 5032, 'enseignant' => 5025, 'competence' => 63],
['personnage' => 5087, 'enseignant' => 4778, 'competence' => 63],
['personnage' => 1889, 'enseignant' => 4778, 'competence' => 63],
['personnage' => 3997, 'enseignant' => 4904, 'competence' => 78],
['personnage' => 5627, 'enseignant' => 497, 'competence' => 68],
['personnage' => 3945, 'enseignant' => 1154, 'competence' => 68],
['personnage' => 2978, 'enseignant' => 2202, 'competence' => 68],
['personnage' => 2113, 'enseignant' => 2504, 'competence' => 68],
['personnage' => 2504, 'enseignant' => 497, 'competence' => 68],
['personnage' => 5319, 'enseignant' => 2043, 'competence' => 68],
['personnage' => 4460, 'enseignant' => 5354, 'competence' => 68],
['personnage' => 5083, 'enseignant' => 2202, 'competence' => 68],
['personnage' => 5631, 'enseignant' => 2978, 'competence' => 68],
['personnage' => 5582, 'enseignant' => 2978, 'competence' => 68],
['personnage' => 5721, 'enseignant' => 497, 'competence' => 68],
['personnage' => 5183, 'enseignant' => 1179, 'competence' => 68],
['personnage' => 1987, 'enseignant' => 2328, 'competence' => 68],
['personnage' => 3134, 'enseignant' => 3157, 'competence' => 68],
['personnage' => 5029, 'enseignant' => 3157, 'competence' => 68],
['personnage' => 3911, 'enseignant' => 497, 'competence' => 71],
['personnage' => 5062, 'enseignant' => 5270, 'competence' => 73],
['personnage' => 4998, 'enseignant' => 2256, 'competence' => 73],
['personnage' => 4965, 'enseignant' => 4998, 'competence' => 73],
['personnage' => 3140, 'enseignant' => 2806, 'competence' => 73],
['personnage' => 2401, 'enseignant' => 3875, 'competence' => 73],
['personnage' => 4124, 'enseignant' => 2887, 'competence' => 73],
['personnage' => 4124, 'enseignant' => 2887, 'competence' => 73],
['personnage' => 5370, 'enseignant' => 5270, 'competence' => 73],
['personnage' => 5049, 'enseignant' => 5048, 'competence' => 73],
['personnage' => 2831, 'enseignant' => 1981, 'competence' => 38],
['personnage' => 5186, 'enseignant' => 5603, 'competence' => 38],
['personnage' => 4964, 'enseignant' => 4964, 'competence' => 38],
['personnage' => 4641, 'enseignant' => 3343, 'competence' => 38],
['personnage' => 5187, 'enseignant' => 1981, 'competence' => 38],
['personnage' => 4419, 'enseignant' => 3343, 'competence' => 38],
['personnage' => 5195, 'enseignant' => 4419, 'competence' => 38],
['personnage' => 5012, 'enseignant' => 4419, 'competence' => 38],
['personnage' => 4015, 'enseignant' => 2286, 'competence' => 38],
['personnage' => 4909, 'enseignant' => 4204, 'competence' => 38],
['personnage' => 2764, 'enseignant' => 4638, 'competence' => 38],
['personnage' => 3932, 'enseignant' => 1981, 'competence' => 38],
['personnage' => 2831, 'enseignant' => 1981, 'competence' => 38],
['personnage' => 3331, 'enseignant' => 2286, 'competence' => 38],
['personnage' => 5046, 'enseignant' => 2828, 'competence' => 41],
['personnage' => 4916, 'enseignant' => 643, 'competence' => 78],
['personnage' => 5210, 'enseignant' => 5650, 'competence' => 78],
['personnage' => 4567, 'enseignant' => 4904, 'competence' => 78],
['personnage' => 5562, 'enseignant' => 5292, 'competence' => 78],
['personnage' => 4114, 'enseignant' => 643, 'competence' => 78],
['personnage' => 5312, 'enseignant' => 5311, 'competence' => 78],
['personnage' => 4326, 'enseignant' => 4084, 'competence' => 78],
['personnage' => 5639, 'enseignant' => 5522, 'competence' => 78],
['personnage' => 4897, 'enseignant' => 643, 'competence' => 78],
['personnage' => 4941, 'enseignant' => 4983, 'competence' => 78],
['personnage' => 5315, 'enseignant' => 5311, 'competence' => 78],
['personnage' => 5141, 'enseignant' => 643, 'competence' => 79],
['personnage' => 4901, 'enseignant' => 2803, 'competence' => 78],
['personnage' => 5234, 'enseignant' => 4947, 'competence' => 2],
['personnage' => 4550, 'enseignant' => 2350, 'competence' => 2],
['personnage' => 5042, 'enseignant' => 68, 'competence' => 2],
['personnage' => 5170, 'enseignant' => 4407, 'competence' => 2],
['personnage' => 4101, 'enseignant' => 2361, 'competence' => 2],
['personnage' => 4995, 'enseignant' => 593, 'competence' => 2],
['personnage' => 4126, 'enseignant' => 68, 'competence' => 2],
['personnage' => 5102, 'enseignant' => 2983, 'competence' => 87],
['personnage' => 5380, 'enseignant' => 4895, 'competence' => 87],
['personnage' => 4406, 'enseignant' => 85, 'competence' => 87],
['personnage' => 3943, 'enseignant' => 5073, 'competence' => 87],
['personnage' => 4908, 'enseignant' => 5764, 'competence' => 87],
['personnage' => 5480, 'enseignant' => 794, 'competence' => 87],
['personnage' => 3584, 'enseignant' => 4895, 'competence' => 87],
['personnage' => 3931, 'enseignant' => 3664, 'competence' => 87],
['personnage' => 5494, 'enseignant' => 5764, 'competence' => 87],
['personnage' => 4997, 'enseignant' => 832, 'competence' => 87],
['personnage' => 5099, 'enseignant' => 526, 'competence' => 87],
['personnage' => 5074, 'enseignant' => 5073, 'competence' => 87],
['personnage' => 5026, 'enseignant' => 2971, 'competence' => 87],
['personnage' => 4958, 'enseignant' => 794, 'competence' => 87],
['personnage' => 5115, 'enseignant' => 832, 'competence' => 87],
['personnage' => 5657, 'enseignant' => 3463, 'competence' => 87],
['personnage' => 4962, 'enseignant' => 794, 'competence' => 87],
['personnage' => 5481, 'enseignant' => 794, 'competence' => 87],
['personnage' => 4462, 'enseignant' => 2794, 'competence' => 87],
['personnage' => 483, 'enseignant' => 85, 'competence' => 87],
['personnage' => 5739, 'enseignant' => 3854, 'competence' => 87],
['personnage' => 5021, 'enseignant' => 2858, 'competence' => 87],
['personnage' => 5090, 'enseignant' => 107, 'competence' => 92],
['personnage' => 5090, 'enseignant' => 107, 'competence' => 92],
['personnage' => 2968, 'enseignant' => 4203, 'competence' => 92],
['personnage' => 85, 'enseignant' => 1584, 'competence' => 92],
['personnage' => 4154, 'enseignant' => 4203, 'competence' => 92],
['personnage' => 3013, 'enseignant' => 591, 'competence' => 92],
['personnage' => 2201, 'enseignant' => 4979, 'competence' => 92],
['personnage' => 5251, 'enseignant' => 5203, 'competence' => 92],
['personnage' => 3905, 'enseignant' => 794, 'competence' => 92],
['personnage' => 4936, 'enseignant' => 5617, 'competence' => 92],
['personnage' => 5034, 'enseignant' => 4979, 'competence' => 92],
['personnage' => 2955, 'enseignant' => 951, 'competence' => 92],
['personnage' => 5336, 'enseignant' => 4061, 'competence' => 92],
['personnage' => 4898, 'enseignant' => 107, 'competence' => 94],
['personnage' => 3597, 'enseignant' => 4203, 'competence' => 96],
['personnage' => 5268, 'enseignant' => 4203, 'competence' => 96],
['personnage' => 4078, 'enseignant' => 3593, 'competence' => 96],
['personnage' => 5506, 'enseignant' => 3584, 'competence' => 132],
['personnage' => 135, 'enseignant' => 2255, 'competence' => 63],
['personnage' => 4186, 'enseignant' => 5424, 'competence' => 97],
['personnage' => 4912, 'enseignant' => 2155, 'competence' => 97],
['personnage' => 4188, 'enseignant' => 3225, 'competence' => 97],
['personnage' => 3477, 'enseignant' => 3985, 'competence' => 97],
['personnage' => 4966, 'enseignant' => 5252, 'competence' => 97],
['personnage' => 5235, 'enseignant' => 4043, 'competence' => 97],
['personnage' => 3875, 'enseignant' => 2401, 'competence' => 97],
['personnage' => 2328, 'enseignant' => 628, 'competence' => 97],
['personnage' => 5209, 'enseignant' => 284, 'competence' => 97],
['personnage' => 2983, 'enseignant' => 2001, 'competence' => 97],
['personnage' => 5400, 'enseignant' => 5055, 'competence' => 97],
['personnage' => 5400, 'enseignant' => 5055, 'competence' => 97],
['personnage' => 4687, 'enseignant' => 5055, 'competence' => 97],
['personnage' => 2254, 'enseignant' => 3225, 'competence' => 97],
['personnage' => 2192, 'enseignant' => 2845, 'competence' => 97],
['personnage' => 4969, 'enseignant' => 1031, 'competence' => 97],
['personnage' => 4173, 'enseignant' => 5011, 'competence' => 97],
['personnage' => 2829, 'enseignant' => 4283, 'competence' => 97],
['personnage' => 3386, 'enseignant' => 3505, 'competence' => 87],
['personnage' => 3375, 'enseignant' => 3313, 'competence' => 107],
['personnage' => 4574, 'enseignant' => 2845, 'competence' => 107],
['personnage' => 3254, 'enseignant' => 3313, 'competence' => 107],
['personnage' => 2045, 'enseignant' => 3254, 'competence' => 107],
['personnage' => 5404, 'enseignant' => 3223, 'competence' => 107],
['personnage' => 4987, 'enseignant' => 4167, 'competence' => 107],
['personnage' => 5679, 'enseignant' => 3223, 'competence' => 107],
['personnage' => 5192, 'enseignant' => 3223, 'competence' => 107],
['personnage' => 5149, 'enseignant' => 1268, 'competence' => 112],
['personnage' => 2785, 'enseignant' => 2786, 'competence' => 112],
['personnage' => 4465, 'enseignant' => 2951, 'competence' => 112],
['personnage' => 3363, 'enseignant' => 711, 'competence' => 112],
['personnage' => 3363, 'enseignant' => 711, 'competence' => 112],
['personnage' => 2854, 'enseignant' => 3121, 'competence' => 112],
['personnage' => 5459, 'enseignant' => 267, 'competence' => 112],
['personnage' => 4457, 'enseignant' => 2794, 'competence' => 112],
['personnage' => 4253, 'enseignant' => 4229, 'competence' => 112],
['personnage' => 5073, 'enseignant' => 3943, 'competence' => 112],
['personnage' => 4000, 'enseignant' => 3121, 'competence' => 112],
['personnage' => 3059, 'enseignant' => 4951, 'competence' => 112],
['personnage' => 1581, 'enseignant' => 5385, 'competence' => 112],
['personnage' => 4253, 'enseignant' => 4229, 'competence' => 112],
['personnage' => 5143, 'enseignant' => 3956, 'competence' => 112],
['personnage' => 3121, 'enseignant' => 2951, 'competence' => 112],
['personnage' => 5144, 'enseignant' => 4399, 'competence' => 112],
['personnage' => 4237, 'enseignant' => 3943, 'competence' => 112],
['personnage' => 4431, 'enseignant' => 5719, 'competence' => 117],
['personnage' => 4963, 'enseignant' => 2829, 'competence' => 117],
['personnage' => 3596, 'enseignant' => 5300, 'competence' => 117],
['personnage' => 3422, 'enseignant' => 1011, 'competence' => 117],
['personnage' => 4200, 'enseignant' => 3398, 'competence' => 122],
['personnage' => 4604, 'enseignant' => 3946, 'competence' => 127],
['personnage' => 4337, 'enseignant' => 3946, 'competence' => 127],
['personnage' => 5498, 'enseignant' => 3510, 'competence' => 127],
['personnage' => 2927, 'enseignant' => 4778, 'competence' => 127],
['personnage' => 4113, 'enseignant' => 3422, 'competence' => 127],
['personnage' => 3223, 'enseignant' => 4778, 'competence' => 127],
['personnage' => 5055, 'enseignant' => 5291, 'competence' => 127],
['personnage' => 5087, 'enseignant' => 4778, 'competence' => 127],
['personnage' => 4603, 'enseignant' => 5291, 'competence' => 127],
['personnage' => 5165, 'enseignant' => 3946, 'competence' => 128],
['personnage' => 3370, 'enseignant' => 3584, 'competence' => 132],
['personnage' => 5301, 'enseignant' => 3584, 'competence' => 132],
['personnage' => 4907, 'enseignant' => 3584, 'competence' => 132],
['personnage' => 5543, 'enseignant' => 5562, 'competence' => 132],
['personnage' => 5292, 'enseignant' => 3480, 'competence' => 132],
['personnage' => 5571, 'enseignant' => 4226, 'competence' => 132],
['personnage' => 4348, 'enseignant' => 3446, 'competence' => 132],
['personnage' => 1348, 'enseignant' => 1143, 'competence' => 132],
['personnage' => 2860, 'enseignant' => 5562, 'competence' => 132],
['personnage' => 4148, 'enseignant' => 2993, 'competence' => 132],
['personnage' => 3446, 'enseignant' => 4226, 'competence' => 132],
['personnage' => 4831, 'enseignant' => 3584, 'competence' => 132],
['personnage' => 4361, 'enseignant' => 804, 'competence' => 132],
['personnage' => 4993, 'enseignant' => 4932, 'competence' => 142],
['personnage' => 5132, 'enseignant' => 681, 'competence' => 142],
['personnage' => 119, 'enseignant' => 681, 'competence' => 142],
['personnage' => 5570, 'enseignant' => 681, 'competence' => 142],
['personnage' => 4167, 'enseignant' => 5744, 'competence' => 142],
['personnage' => 5113, 'enseignant' => 681, 'competence' => 142],
['personnage' => 3350, 'enseignant' => 4110, 'competence' => 142],
['personnage' => 5031, 'enseignant' => 2023, 'competence' => 142],
['personnage' => 3592, 'enseignant' => 681, 'competence' => 142],
['personnage' => 427, 'enseignant' => 2609, 'competence' => 142],
['personnage' => 3463, 'enseignant' => 681, 'competence' => 142],
['personnage' => 2609, 'enseignant' => 5116, 'competence' => 142],
['personnage' => 2828, 'enseignant' => 681, 'competence' => 142],
['personnage' => 4210, 'enseignant' => 4110, 'competence' => 142],
['personnage' => 2828, 'enseignant' => 681, 'competence' => 142],
['personnage' => 2629, 'enseignant' => 4645, 'competence' => 142],
['personnage' => 5120, 'enseignant' => 681, 'competence' => 142],
['personnage' => 5559, 'enseignant' => 681, 'competence' => 142],
['personnage' => 5206, 'enseignant' => 5512, 'competence' => 142],
['personnage' => 3978, 'enseignant' => 124, 'competence' => 143],
['personnage' => 3978, 'enseignant' => 124, 'competence' => 143],
['personnage' => 5510, 'enseignant' => 4110, 'competence' => 144],
['personnage' => 5318, 'enseignant' => 124, 'competence' => 143],
['personnage' => 3593, 'enseignant' => 107, 'competence' => 95],
['personnage' => 5293, 'enseignant' => 2491, 'competence' => 147],
['personnage' => 5678, 'enseignant' => 1889, 'competence' => 147],
['personnage' => 4792, 'enseignant' => 2165, 'competence' => 147],
['personnage' => 5678, 'enseignant' => 1889, 'competence' => 147],
['personnage' => 5682, 'enseignant' => 2165, 'competence' => 147],
['personnage' => 2211, 'enseignant' => 4400, 'competence' => 147],
['personnage' => 3406, 'enseignant' => 2165, 'competence' => 147],
['personnage' => 4400, 'enseignant' => 2165, 'competence' => 147],
['personnage' => 4110, 'enseignant' => 1173, 'competence' => 147],
['personnage' => 3974, 'enseignant' => 2165, 'competence' => 147],
['personnage' => 4658, 'enseignant' => 1231, 'competence' => 147],
['personnage' => 4512, 'enseignant' => 87, 'competence' => 148],
['personnage' => 3227, 'enseignant' => 3370, 'competence' => 152],
['personnage' => 5780, 'enseignant' => 3370, 'competence' => 152],
['personnage' => 3626, 'enseignant' => 77, 'competence' => 152],
['personnage' => 5780, 'enseignant' => 3370, 'competence' => 152],
['personnage' => 5116, 'enseignant' => 2609, 'competence' => 152],
['personnage' => 112, 'enseignant' => 2648, 'competence' => 152],
['personnage' => 3918, 'enseignant' => 4432, 'competence' => 152],
['personnage' => 4001, 'enseignant' => 5443, 'competence' => 152],
['personnage' => 124, 'enseignant' => 2023, 'competence' => 152],
['personnage' => 5071, 'enseignant' => 3370, 'competence' => 152],
['personnage' => 4001, 'enseignant' => 5443, 'competence' => 152],
['personnage' => 5118, 'enseignant' => 2609, 'competence' => 152],
['personnage' => 3227, 'enseignant' => 3370, 'competence' => 152],
['personnage' => 4346, 'enseignant' => 2609, 'competence' => 152],
['personnage' => 3438, 'enseignant' => 2080, 'competence' => 23],
['personnage' => 1999, 'enseignant' => 3365, 'competence' => 157],
['personnage' => 5174, 'enseignant' => 3055, 'competence' => 157],
['personnage' => 5190, 'enseignant' => 4114, 'competence' => 157],
['personnage' => 5443, 'enseignant' => 4955, 'competence' => 162],
['personnage' => 5313, 'enseignant' => 5315, 'competence' => 162],
['personnage' => 5274, 'enseignant' => 5315, 'competence' => 162],
['personnage' => 4137, 'enseignant' => 479, 'competence' => 162],
['personnage' => 5311, 'enseignant' => 5315, 'competence' => 162],
        ];

        foreach ($datas as $i => $data) {

            $personnage = $this->entityManager->find(Personnage::class, $data['personnage']);
            if (!$personnage) {
                $io->error('Personnage '.$data['personnage'].' introuvable à la ligne '.$i + 1);
                continue;
            }

            $competence = $this->entityManager->find(Competence::class, $data['competence']);
            if (!$competence) {
                $io->error('Competence '.$data['competence'].' introuvable pour le PJ '.$personnage->getId().'  à la ligne '.$i + 1);
                continue;
            }

            $enseignant = $this->entityManager->find(Personnage::class, $data['enseignant']);
            if (!$enseignant) {
                $io->error('Enseignant '.$data['enseignant'].' introuvable à la ligne '.$i + 1);
                continue;
            }

            $availableCompetences = $this->personnageService->getApprentissageCompetences($personnage);

            // Check the right level of competence if player knew previous one
            $competenceAvailable = $competence;
            do {
                if (!$competenceAvailable) {
                    break;
                }
                if (!$availableCompetences->contains($competenceAvailable)) {
                    $competenceAvailable = $competenceAvailable->getNext();
                    break;
                }
            } while ($competenceAvailable?->getLevel()?->getIndex() <= Level::NIVEAU_4);

            if (!$competenceAvailable) {
                $io->error(
                    sprintf(
                        'La compétence %s ne fait pas partie des compétences actuellement accessibles pour le personnage %s',
                        $competence->getLabel(),
                        $personnage->getIdName(),
                    ),
                );
                continue;
            }

            if (!$this->personnageService->canTeachCompetence($enseignant, $competenceAvailable)) {
                $io->error(
                    sprintf(
                        "L'enseignant: %s. Ne peut pas enseigner %s. Il doit pour cela connaitre la compétence au moins au niveau expert.",
                        $enseignant->getIdName(),
                        $competenceAvailable->getLabel(),
                    ),
                );
                continue;
            }

            $personnageApprentissage = new PersonnageApprentissage();
            $personnageApprentissage->setPersonnage($personnage);
            $personnageApprentissage->setEnseignant($enseignant);
            $personnageApprentissage->setCompetence($competenceAvailable);
            $personnageApprentissage->setCreatedAt(new \DateTime());
            $personnageApprentissage->setDateEnseignement(1326);
            $this->entityManager->persist($personnageApprentissage);
            $this->entityManager->flush();
        }

        $io->success('Terminé');

        return Command::SUCCESS;
    }
}
