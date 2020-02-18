var fs = require('fs');

var str = fs.readFileSync('categories.json');
var obj = JSON.parse(str);

const {
    service_families,
    services,
    categories
} = obj;

const sep = " → ";

for (const service_family of service_families.choices) {
    const {name: sf_name, id: sf_id} = service_family;

    if (service_families.dependencies[sf_id]) {
        for (const d_service of service_families.dependencies[sf_id]["Service"]) {
            const service = services.choices.filter(x => x.name === d_service)[0];
            const {name: s_name, id: s_id} = service;

            if (services.dependencies[s_id] && s_id !== "Other") {
                for (const d_category of services.dependencies[s_id]["Category"]) {
                    const category = categories.choices.filter(x => x.name === d_category)[0];
                    const {name: c_name, id: c_id} = category;

                    if (categories.dependencies[c_id] && c_id !== "Other") {
                        for (const d_subcategory of categories.dependencies[c_id]["Sub__uCategory"]) {
                            const sc_name = d_subcategory;

                            console.log([sf_name, s_name, c_name, sc_name].join(sep));
                        }
                    } else {
                        console.log([sf_name, s_name, c_name].join(sep));
                    }
                }
            } else {
                console.log([sf_name, s_name].join(sep));
            }
        }
    } else {
        console.log(sf_name);
    }
}
