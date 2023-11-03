
class DonutChart {
    constructor(percentageOuter, percentageInner) {
        this.percentageOuter = percentageOuter;
        this.percentageOuterEmpty = 100 - this.percentageOuter;
        this.percentageInnerEmpty = (100 - percentageInner) * 0.75;
        this.percentageInner = percentageInner * 0.75;
    }

    render = () => {
        var chart = document.createElement("div");
        chart.classList.add('svg-item');

        const outer_ring = `
        <circle class="donut-ring" cx="20" cy="20" r="16" fill="transparent" stroke-width="3.5"/>
        <circle class="donut-segment-outer" cx="20" cy="20" r="16" fill="transparent" stroke-width="4" 
            stroke-dasharray="${this.percentageOuter} ${this.percentageOuterEmpty}"
            stroke-dashoffset="25"/>`;

        const inner_ring = this.percentageInner !== null? `
        <circle class="donut-ring" cx="20" cy="20" r="12" fill="transparent" stroke-width="3.5"/>
        <circle class="donut-segment-inner" cx="20" cy="20" r="12" fill="transparent" stroke-width="4"
            stroke-dasharray="${this.percentageInner} ${this.percentageInnerEmpty}"
            stroke-dashoffset="18.75"></circle>`
            : "";

        chart.innerHTML = `<svg width="100%" height="100%" viewBox="0 0 40 40" class="donut">
                <circle class="donut-hole" cx="20" cy="20" r="15.91549430918954" fill="#fff"></circle>
                ${outer_ring}
                ${inner_ring}
            </svg>`;

        chart.getElementsByClassName('donut-segment-outer')[0].animate(
            [
                { strokeDasharray: "0, 100" },
                { strokeDasharray: `${this.percentageOuter} ${this.percentageOuterEmpty}`}
            ], {duration: 3000}
        );
        chart.getElementsByClassName('donut-segment-inner')[0].animate(
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