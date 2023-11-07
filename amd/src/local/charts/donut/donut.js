
class DonutChart {
    constructor(percentageOuter, legendOuter, percentageInner, legendInner) {
        this.percentageOuter = percentageOuter;
        this.legendOuter = legendOuter;
        this.percentageOuterEmpty = 100 - this.percentageOuter;
        this.percentageInnerEmpty = (100 - percentageInner) * 0.75;
        this.percentageInner = percentageInner * 0.75;
        this.legendInner = legendInner;
    }

    getTextWidth = (text, font) => {
        const element = document.createElement('canvas');
        const context = element.getContext('2d');
        context.font = font;
        return context.measureText(text).width;
    };

    render = () => {
        var chart = document.createElement("div");
        chart.classList.add('block_chatbot-svg-chart');

        const font = '5px serif';
        const outerTextWidth = this.getTextWidth(this.legendOuter, font);
        const innerTextWidth = this.legendInner !== null? this.getTextWidth(this.legendInner, font) : 0;
        const totalWidth = 40 + 3 + 7 + Math.max(outerTextWidth, innerTextWidth); // chart size + spacing + rect + legend

        const outer_ring = `
        <circle class="block_chatbot-donut-ring" cx="20" cy="20" r="16" fill="transparent" stroke-width="3.5"/>
        <circle class="block_chatbot-donut-segment-outer" cx="20" cy="20" r="16" fill="transparent" stroke-width="4" 
            stroke-dasharray="${this.percentageOuter} ${this.percentageOuterEmpty}"
            stroke-dashoffset="25"/>`;
        const outer_legend = `<g>
                                <rect x="0" y="0" width="4" height="4" fill="#8de239"/>
                                <text x="7" y="2.5" font-size="5" alignment-baseline="middle" fill="white">
                                    ${this.legendOuter}
                                </text>
                            </g>`;

        const inner_ring = this.percentageInner !== null? `
        <circle class="block_chatbot-donut-ring" cx="20" cy="20" r="12" fill="transparent" stroke-width="3.5"/>
        <circle class="block_chatbot-donut-segment-inner" cx="20" cy="20" r="12" fill="transparent" stroke-width="4"
            stroke-dasharray="${this.percentageInner} ${this.percentageInnerEmpty}"
            stroke-dashoffset="18.75"></circle>`
            : "";
        const inner_legend = this.percentageInner !== null? `<g>
                <rect x="0" y="7" width="4" height="4" fill="#ff6200"/>
                <text x="7" y="9.5" font-size="5" alignment-baseline="middle" fill="white">
                    ${this.legendInner}
                </text>
            </g>` : "";

        chart.innerHTML = `<svg width="100%" height="100%" viewBox="0 0 ${totalWidth} 40" class="block_chatbot-donut">
                <circle class="block_chatbot-donut-hole" cx="20" cy="20" r="15.91549430918954" fill="#fff"/>
                ${outer_ring}
                ${inner_ring}
                <g transform="translate(43 17.5)">
                    ${outer_legend}
                    ${inner_legend}
                </g>
            </svg>`;

        chart.getElementsByClassName('block_chatbot-donut-segment-outer')[0].animate(
            [
                { strokeDasharray: "0, 100" },
                { strokeDasharray: `${this.percentageOuter} ${this.percentageOuterEmpty}`}
            ], {duration: 3000}
        );
        chart.getElementsByClassName('block_chatbot-donut-segment-inner')[0].animate(
            [
                { strokeDasharray: "0, 75" },
                { strokeDasharray: `${this.percentageInner} ${this.percentageInnerEmpty}`}
            ], {duration: 3000}
        );

        return chart;
        //     <g class="donut-text">
        //     <text y="50%" transform="translate(0, 2)">
        //     <tspan x="50%" text-anchor="middle" class="donut-percent">${this.percentage}%</tspan>
        //     </text>
        // </g>
    };
}

export default DonutChart;