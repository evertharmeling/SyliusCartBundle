<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\CartBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sylius\Bundle\CartBundle\SyliusCartEvents;
use Sylius\Bundle\CartBundle\Event\CartEvent;
use Sylius\Bundle\CartBundle\Event\FlashEvent;

/**
 * Default cart controller.
 * It extends the format agnostic resource controller.
 * Resource controller class provides several actions and methods for creating
 * pages and api for your cart system.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class CartController extends Controller
{
    /**
     * Displays current cart summary page.
     * The parameters includes the form created from `sylius_cart` type.
     *
     * @param Request
     *
     * @return Response
     */
    public function summaryAction(Request $request)
    {
        $cart = $this->getCurrentCart();
        $form = $this->createForm('sylius_cart', $cart);

        return $this->renderResponse('summary.html', array(
            'cart' => $cart,
            'form' => $form->createView()
        ));
    }

    /**
     * This action is used to submit the cart summary form.
     * If the form and updated cart are valid, it refreshes
     * the cart data and saves it using the operator.
     *
     * If there are any errors, it displays the cart summary page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function saveAction(Request $request)
    {
        $cart = $this->getCurrentCart();
        $form = $this->createForm('sylius_cart', $cart);

        if ($form->bind($request)->isValid()) {
            /* @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
            $dispatcher = $this->container->get('event_dispatcher');

            $event = new CartEvent($cart);
            $event->isFresh(true);

            $dispatcher->dispatch(SyliusCartEvents::CART_SAVE_INITIALIZE, $event);
            $dispatcher->dispatch(SyliusCartEvents::CART_SAVE_COMPLETED, new FlashEvent());
        }

        return $this->renderResponse('summary.html', array(
            'cart' => $cart,
            'form' => $form->createView()
        ));
    }

    /**
     * Clears the current cart using the operator.
     * By default it redirects to cart summary page.
     *
     * @return Response
     */
    public function clearAction()
    {
        /* @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch(SyliusCartEvents::CART_CLEAR_INITIALIZE, new CartEvent($this->getCurrentCart()));
        $dispatcher->dispatch(SyliusCartEvents::CART_CLEAR_COMPLETED, new FlashEvent());

        return $this->redirectToCartSummary();
    }
}
