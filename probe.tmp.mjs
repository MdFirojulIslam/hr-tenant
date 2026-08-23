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

page.on("pageerror", (e) => console.log("  [PAGE ERROR]", String(e).slice(0, 200)));
page.on("console", (m) => {
    if (m.type() === "error") console.log("  [CONSOLE ERROR]", m.text().slice(0, 200));
});
page.on("response", async (r) => {
    const u = r.url();
    if (
        u.includes("department-list") ||
        u.includes("employee-list-by-department") ||
        u.includes("outlet-visit-report")
    ) {
        console.log("  [API]", r.status(), u.replace(APP, "").slice(0, 80));
    }
});

await page.evaluateOnNewDocument((tok) => {
    localStorage.setItem("token", tok);
    localStorage.setItem("roleId", "1");
    localStorage.setItem("permissions", JSON.stringify([77]));
    localStorage.setItem("user", JSON.stringify({ id: 6, Full_Name: "Saad" }));
    localStorage.setItem("tokenExpiration", String(Date.now() + 3600000));
}, token);

await page.goto(`${APP}${ROUTE}`, { waitUntil: "networkidle2", timeout: 90000 });
await page.waitForSelector(".custom-payroll-table tbody tr", { timeout: 30000 });
await sleep(2000);

console.log("\n--- multiselect DOM probe ---");
const probe1 = await page.evaluate(() => {
    const c = document.querySelector(
        ".report-wrapper .multiselect-department-container",
    );
    if (!c) return { found: false };
    return {
        found: true,
        hasTags: !!c.querySelector(".multiselect__tags"),
        hasSelectArrow: !!c.querySelector(".multiselect__select"),
        singleLabel: c.querySelector(".multiselect__single")?.textContent.trim() ?? null,
        placeholder: c.querySelector(".multiselect__placeholder")?.textContent.trim() ?? null,
        optionCount: c.querySelectorAll(".multiselect__option").length,
        wrapper: c.querySelector(".multiselect__content-wrapper") ? c.querySelector(".multiselect__content-wrapper").className : null,
    };
});
console.log(JSON.stringify(probe1, null, 1));

// try opening via real mouse click on tags
console.log("\n--- clicking dept multiselect ---");
const tags = await page.$(".report-wrapper .multiselect-department-container .multiselect__tags");
if (!tags) throw new Error("tags element not found");
await tags.click();
await sleep(800);

const probe2 = await page.evaluate(() => {
    const c = document.querySelector(
        ".report-wrapper .multiselect-department-container",
    );
    const opts = [...c.querySelectorAll(".multiselect__option")];
    return {
        optionCount: opts.length,
        first10: opts.slice(0, 12).map((o) => o.textContent.trim()),
        contentClass: c.querySelector(".multiselect__content-wrapper")?.className,
    };
});
console.log(JSON.stringify(probe2, null, 1));

await browser.close();
console.log("\nPROBE DONE");
