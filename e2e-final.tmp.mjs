import puppeteer from "puppeteer-core";
import fs from "fs";

const T = "C:/Users/firoj/AppData/Local/Temp/opencode/";
const token = fs.readFileSync(T + "token.txt", "utf8").trim();
const APP = "http://localhost:8000/";
const ROUTE = "outlet-visit/outlet-visit-report";
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await puppeteer.launch({
    executablePath: "C:/Program Files/Google/Chrome/Application/chrome.exe",
    headless: "new",
    args: ["--no-sandbox", "--disable-gpu"],
});
const page = await browser.newPage();
page.setDefaultTimeout(60000);

let pageErrors = [];
page.on("pageerror", (e) => {
    const msg = String(e).slice(0, 200);
    pageErrors.push(msg);
    console.log("  [PAGE ERROR]", msg);
});
page.on("console", (m) => {
    if (m.type() === "error") console.log("  [CONSOLE ERROR]", m.text().slice(0, 200));
});

await page.evaluateOnNewDocument((tok) => {
    localStorage.setItem("token", tok);
    localStorage.setItem("roleId", "1");
    localStorage.setItem("permissions", JSON.stringify([77]));
    localStorage.setItem("user", JSON.stringify({ id: 6, Full_Name: "Saad" }));
    localStorage.setItem("tokenExpiration", String(Date.now() + 3600000));
}, token);

async function loadPage() {
    await page.goto(`${APP}${ROUTE}`, { waitUntil: "networkidle2", timeout: 90000 });
    await page.waitForSelector(".custom-payroll-table tbody tr", { timeout: 30000 });
    await sleep(1500); // allow department list + report data to settle
}

// ---------- helpers ----------
async function setDate(iso) {
    await page.evaluate((v) => {
        const el = document.querySelector(".report-wrapper input[type=date]");
        const setter = Object.getOwnPropertyDescriptor(
            window.HTMLInputElement.prototype,
            "value",
        ).set;
        setter.call(el, v);
        el.dispatchEvent(new Event("input", { bubbles: true }));
        el.dispatchEvent(new Event("change", { bubbles: true }));
    }, iso);
    await sleep(500);
}

async function pickMultiselect(containerSel, labelText) {
    // open with a REAL click on the tags area
    const tagsHandle = await page.$(`${containerSel} .multiselect__tags`);
    if (!tagsHandle) throw new Error("multiselect tags not found: " + containerSel);
    await tagsHandle.click();
    await sleep(700);

    // find + click the option with a REAL click
    const clicked = await page.evaluate(
        (sel, label) => {
            const c = document.querySelector(sel);
            if (!c) return false;
            const opts = [...c.querySelectorAll(".multiselect__option")];
            const exact =
                opts.find((o) => o.textContent.trim() === label) ||
                opts.find((o) =>
                    o.textContent.trim().toLowerCase().includes(label.toLowerCase()),
                );
            if (!exact) return false;
            exact.click();
            return true;
        },
        containerSel,
        labelText,
    );
    if (!clicked)
        throw new Error(`option "${labelText}" not found in ${containerSel}`);
    await sleep(700);

    // close any remaining dropdown
    await page.keyboard.press("Escape");
    await sleep(300);
}

async function clearEmployeeFilter() {
    const ok = await page.evaluate(() => {
        const c = document.querySelector(".multiselect-employee-container");
        const btn = c?.querySelector(".clear-btn");
        if (!btn) return false;
        btn.click();
        return true;
    });
    if (!ok) throw new Error("employee clear button not found");
    await sleep(600);
}

async function tableSnapshot() {
    return page.evaluate(() => {
        const rows = [
            ...document.querySelectorAll(".custom-payroll-table tbody tr"),
        ].filter((tr) => tr.querySelectorAll("td").length > 5);
        const names = [
            ...new Set(
                rows.map((r) =>
                    r.querySelector("td:nth-child(2)")?.textContent.trim(),
                ),
            ),
        ];
        return { count: rows.length, names };
    });
}

async function employeeDropdownLabel() {
    return page.evaluate(() => {
        const c = document.querySelector(".multiselect-employee-container");
        const single = c?.querySelector(".multiselect__single");
        return single ? single.textContent.trim() : "(placeholder)";
    });
}

async function assertEmployees(expectedNames, title) {
    const snap = await tableSnapshot();
    const label = await employeeDropdownLabel();
    console.log(`  -> ${title}`);
    console.log("     table rows:", snap.count);
    console.log("     employees shown:", snap.names.join(" | "));
    console.log("     employee dropdown:", label);
    for (const n of expectedNames) {
        if (!snap.names.includes(n))
            throw new Error(`${title}: expected "${n}" in table`);
    }
    if (snap.names.length !== expectedNames.length)
        throw new Error(
            `${title}: expected exactly [${expectedNames}] but got [${snap.names}]`,
        );
    return snap;
}

const DEPT_SEL = ".report-wrapper .multiselect-department-container";
const EMP_SEL = ".report-wrapper .multiselect-employee-container";
const results = {};

async function scenario(key, title, steps) {
    console.log("\n===============================");
    console.log(key, "-", title);
    console.log("===============================");
    await loadPage();
    try {
        for (const step of steps) await step();
        results[key] = "PASS";
    } catch (e) {
        results[key] = "FAIL: " + String(e).split("\n")[0];
        console.log("  !! SCENARIO FAILED:", String(e).split("\n")[0]);
    }
}

// ---------- T1 ----------
await scenario(
    "T1",
    "Previous date Aug-18 + Modern Trade + Md Asraful Islam",
    [
        () => setDate("2026-08-18"),
        () => pickMultiselect(DEPT_SEL, "Modern Trade"),
        async () => {
            await sleep(800); // employee list async load
            await pickMultiselect(EMP_SEL, "Md Asraful Islam");
            await sleep(500);
            await assertEmployees(["Md Asraful Islam"], "after selecting Md Asraful Islam");
        },
    ],
);

// ---------- T2 ----------
await scenario(
    "T2",
    "Previous date Aug-18 + Modern Trade + Sujan Sarker (different employee)",
    [
        () => setDate("2026-08-18"),
        () => pickMultiselect(DEPT_SEL, "Modern Trade"),
        async () => {
            await sleep(800);
            await pickMultiselect(EMP_SEL, "Sujan Sarker");
            await sleep(500);
            const snap = await assertEmployees(["Sujan Sarker"], "after selecting Sujan Sarker");
            if (snap.names.includes("Md Asraful Islam"))
                throw new Error("previous employee still visible!");
        },
    ],
);

// ---------- T3 ----------
await scenario(
    "T3",
    "Previous date Aug-18 + Modern Trade only (no employee)",
    [
        () => setDate("2026-08-18"),
        () => pickMultiselect(DEPT_SEL, "Modern Trade"),
        async () => {
            await sleep(800);
            const snap = await tableSnapshot();
            console.log("  -> after selecting Modern Trade");
            console.log("     table rows:", snap.count);
            console.log("     employees shown:", snap.names.join(" | "));
            if (snap.names.length < 5)
                throw new Error("expected multiple Modern Trade employees, got " + snap.names.length);
            const expectedMT = [
                "Afjal Ujjaman Hridoy","Hasan Shahriar Mehedi","MD Yeanur","MD. Mehedi Hasan",
                "Md Asraful Islam","Md. Miraz Akondo","Md. Tarikul Islam","Sohag Mahamud","Sujan Sarker","Swapna Akter Sumi",
            ];
            for (const n of expectedMT)
                if (!snap.names.includes(n)) throw new Error("missing Modern Trade member: " + n);
            const nonMT = ["Azizur Rahman", "Saad Hossain Tapu"];
            for (const n of nonMT)
                if (snap.names.includes(n)) throw new Error("non-Modern-Trade row leaked: " + n);
        },
    ],
);

// ---------- T4 ----------
await scenario(
    "T4",
    "Clear employee filter returns dept-filtered historical data",
    [
        () => setDate("2026-08-18"),
        () => pickMultiselect(DEPT_SEL, "Modern Trade"),
        async () => {
            await sleep(800);
            await pickMultiselect(EMP_SEL, "Md Asraful Islam");
            await sleep(400);
            await assertEmployees(["Md Asraful Islam"], "narrowed to Md Asraful Islam");
        },
        () => clearEmployeeFilter(),
        async () => {
            await sleep(500);
            const snap = await tableSnapshot();
            console.log("  -> after clearing employee filter");
            console.log("     table rows:", snap.count);
            console.log("     employees shown:", snap.names.join(" | "));
            if (snap.names.length < 5)
                throw new Error("clearing filter did not restore dept list, got " + snap.names.length);
            if (
                !snap.names.includes("Sujan Sarker") ||
                !snap.names.includes("Md Asraful Islam")
            )
                throw new Error("dept members missing after clear");
        },
    ],
);

// ---------- T5 ----------
await scenario(
    "T5",
    "CURRENT date Aug-23 regression + Modern Trade + Md Asraful Islam",
    [
        () => setDate("2026-08-23"),
        () => pickMultiselect(DEPT_SEL, "Modern Trade"),
        async () => {
            await sleep(800);
            await pickMultiselect(EMP_SEL, "Md Asraful Islam");
            await sleep(500);
            await assertEmployees(["Md Asraful Islam"], "current-date regression check");
        },
    ],
);

console.log("\n===============================");
console.log("SUMMARY");
console.log("===============================");
for (const [k, v] of Object.entries(results)) console.log(k.padEnd(4), "->", v);

await browser.close();
process.exit(Object.values(results).every((v) => v === "PASS") ? 0 : 1);
