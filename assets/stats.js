// Chart.js pour la page /statistique (servi par l'importmap, sans CDN).
import {Chart, registerables} from 'chart.js';

Chart.register(...registerables);
window.Chart = Chart;
