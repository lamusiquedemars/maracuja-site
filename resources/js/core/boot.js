import { initNavigation } from '../components/navigation';
import { initDisclosure } from '../components/disclosure';
import { initReveal } from '../components/reveal';
import { initCarousel } from '../components/carousel';
import { initLightbox } from '../components/lightbox';
import { initFormStates } from '../components/form';
import { initBackToTop } from '../components/back-to-top';
import { initConversation } from '../components/conversation';
import { initAssistant } from '../components/assistant';

const initializers = [
    initNavigation,
    initDisclosure,
    initReveal,
    initCarousel,
    initLightbox,
    initFormStates,
    initBackToTop,
    initConversation,
    initAssistant,
];

export function bootMaracujaUi(root = document) {
    initializers.forEach((init) => init(root));
}
