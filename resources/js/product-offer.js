import '../css/product-offer.css'
import OfferEditor from './components/Product/Offer/OfferEditor.vue'
import OfferList from './components/Product/Offer/OfferList.vue'
import { mountVueApp } from './utils/createVueApp.js'

mountVueApp(OfferList, 'product-offer-list')
mountVueApp(OfferEditor, 'product-offer-editor')
