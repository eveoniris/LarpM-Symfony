<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Controller;

use App\Enum\Role;
use App\Form\PnjInscriptionForm;
use App\Manager\GroupeManager;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// TODO
#[IsGranted(new MultiRolesExpression(Role::ORGA))]
class PnjController extends AbstractController
{
    /**
     *
     */
    public function listAction(Request $request, EntityManagerInterface $entityManager)
    {
        $gn = GroupeManager::getGnActif($entityManager);

        $pnjs = $gn->getParticipantsPnj();

        return $this->render('pnj/list.twig', [
            'pnjs' => $pnjs,
        ]);
    }
}
