<?php
/*
 Copyright (C) AC SOFTWARE SP. Z O.O.
 
 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace SuplaBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SuplaBundle\Model\Audit\FailedAuthAttemptsUserBlocker;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;

class DefaultController extends Controller {
    /** @var FailedAuthAttemptsUserBlocker */
    private $failedAuthAttemptsUserBlocker;

    public function __construct(FailedAuthAttemptsUserBlocker $failedAuthAttemptsUserBlocker) {
        $this->failedAuthAttemptsUserBlocker = $failedAuthAttemptsUserBlocker;
    }

    /**
     * @Route("/auth/create", name="_auth_create")
     * @Route("/account/create", name="_account_create")
     * @Route("/account/create_here/{locale}", name="_account_create_here")
     */
    public function createAction(Request $request, $locale = null) {
        if ($this->getUser()) {
            return $this->redirectToRoute('_homepage');
        }
        return $this->redirectToRoute('_register', ['lang' => $request->getLocale()]);
    }

    /**
     * @Route("/oauth-authorize", name="_oauth_login")
     * @Route("/oauth/v2/auth_login", name="_oauth_login_check")
     * @Template()
     */
    public function oAuthLoginAction(Request $request) {
        $session = $request->getSession();

        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        }

        $lastUsername = $session->get(Security::LAST_USERNAME);
        if ($error) {
            $error = 'error';
            if ($lastUsername && $this->failedAuthAttemptsUserBlocker->isAuthenticationFailureLimitExceeded($lastUsername)) {
                $error = 'locked';
            }
        }

        return ['last_username' => $lastUsername, 'error' => $error];
    }

    /**
     * @Route("/", name="_homepage")
     * @Route("/register", name="_register")
     * @Route("/auth/login", name="_obsolete_login")
     * @Route("/{suffix}", requirements={"suffix"="^(?!api|oauth/|direct/).*"}, methods={"GET"})
     * @Template()
     */
    public function spaBoilerplateAction($suffix = null) {
        if ($suffix && preg_match('#\..{2,4}$#', $suffix)) {
            throw new NotFoundHttpException("$suffix file could not be found");
        }
    }
}
