<script setup>
import {
    ref,
    computed,
    onMounted,
    onBeforeUnmount,
    watch,
    nextTick,
} from "vue";
import { useStore } from "vuex";
import api from "@/api";
import Swal from "sweetalert2";
import BaseDept from "@/pages/components/input_field/BaseDept.vue";
import SelectField from "@/pages/components/input_field/SelectField.vue";
import BaseDeptEmp from "@/pages/components/input_field/BaseDeptEmp.vue";
import DateField from "@/pages/components/input_field/DateField.vue";
import DayName from "@/pages/components/input_field/DayName.vue";

const store = useStore();
const user = store.getters.getUser;
const userID = user ? user.id : null;

const loading = ref(false);
const downloading = ref(false);
const previewImage = ref(null);

const DUMMY_IMAGE =
    "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEBLAEsAAD/4QCXRXhpZgAASUkqAAgAAAADAA4BAgBNAAAAMgAAABoBBQABAAAAfwAAABsBBQABAAAAhwAAAAAAAABVc2VyIEljb24gRmxhdCBJc29sYXRlZCBvbiBXaGl0ZSBCYWNrZ3JvdW5kLiBVc2VyIFN5bWJvbC4gVmVjdG9yIElsbHVzdHJhdGlvbiwBAAABAAAALAEAAAEAAAD/4QXAaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/Pgo8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIj4KCTxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+CgkJPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIiB4bWxuczpJcHRjNHhtcENvcmU9Imh0dHA6Ly9pcHRjLm9yZy9zdGQvSXB0YzR4bXBDb3JlLzEuMC94bWxucy8iICAgeG1sbnM6R2V0dHlJbWFnZXNHSUZUPSJodHRwOi8veG1wLmdldHR5aW1hZ2VzLmNvbS9naWZ0LzEuMC8iIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgeG1sbnM6cGx1cz0iaHR0cDovL25zLnVzZXBsdXMub3JnL2xkZi94bXAvMS4wLyIgIHhtbG5zOmlwdGNFeHQ9Imh0dHA6Ly9pcHRjLm9yZy9zdGQvSXB0YzR4bXBFeHQvMjAwOC0wMi0yOS8iIHhtbG5zOnhtcFJpZ2h0cz0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3JpZ2h0cy8iIHBob3Rvc2hvcDpDcmVkaXQ9IkdldHR5IEltYWdlcyIgR2V0dHlJbWFnZXNHSUZUOkFzc2V0SUQ9IjEzMDA4NDU2MjAiIHhtcFJpZ2h0czpXZWJTdGF0ZW1lbnQ9Imh0dHBzOi8vd3d3LmlzdG9ja3Bob3RvLmNvbS9sZWdhbC9saWNlbnNlLWFncmVlbWVudD91dG1fbWVkaXVtPW9yZ2FuaWMmYW1wO3V0bV9zb3VyY2U9Z29vZ2xlJmFtcDt1dG1fY2FtcGFpZ249aXB0Y3VybCIgcGx1czpEYXRhTWluaW5nPSJodHRwOi8vbnMudXNlcGx1cy5vcmcvbGRmL3ZvY2FiL0RNSS1QUk9ISUJJVEVELUVYQ0VQVFNFQVJDSEVOR0lORUlOREVYSU5HIiA+CjxkYzpjcmVhdG9yPjxyZGY6U2VxPjxyZGY6bGk+UGV0ZXJQZW5jaWw8L3JkZjpsaT48L3JkZjpTZXE+PC9kYzpjcmVhdG9yPjxkYzpkZXNjcmlwdGlvbj48cmRmOkFsdD48cmRmOmxpIHhtbDpsYW5nPSJ4LWRlZmF1bHQiPlVzZXIgSWNvbiBGbGF0IElzb2xhdGVkIG9uIFdoaXRlIEJhY2tncm91bmQuIFVzZXIgU3ltYm9sLiBWZWN0b3IgSWxsdXN0cmF0aW9uPC9yZGY6bGk+PC9yZGY6QWx0PjwvZGM6ZGVzY3JpcHRpb24+CjxwbHVzOkxpY2Vuc29yPjxyZGY6U2VxPjxyZGY6bGkgcmRmOnBhcnNlVHlwZT0nUmVzb3VyY2UnPjxwbHVzOkxpY2Vuc29yVVJMPmh0dHBzOi8vd3d3LmlzdG9ja3Bob3RvLmNvbS9waG90by9saWNlbnNlLWdtMTMwMDg0NTYyMC0/dXRtX21lZGl1bT1vcmdhbmljJmFtcDt1dG1fc291cmNlPWdvb2dsZSZhbXA7dXRtX2NhbXBhaWduPWlwdGN1cmw8L3BsdXM6TGljZW5zb3JVUkw+PC9yZGY6bGk+PC9yZGY6U2VxPjwvcGx1czpMaWNlbnNvcj4KCQk8L3JkZjpEZXNjcmlwdGlvbj4KCTwvcmRmOlJERj4KPC94OnhtcG1ldGE+Cjw/eHBhY2tldCBlbmQ9InciPz4K/+0AkFBob3Rvc2hvcCAzLjAAOEJJTQQEAAAAAABzHAJQAAtQZXRlclBlbmNpbBwCeABNVXNlciBJY29uIEZsYXQgSXNvbGF0ZWQgb24gV2hpdGUgQmFja2dyb3VuZC4gVXNlciBTeW1ib2wuIFZlY3RvciBJbGx1c3RyYXRpb24cAm4ADEdldHR5IEltYWdlcwD/2wBDAAoH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AAAAACwAAAB4AAAAAAAI4AAAAAAAAAAAAAA5AAAAAAAGAAAAAAwAAAAAABwwAAAAAAAAAAAAAwwAAAAAAGAAAAAAA4AAAAAAIHAAAAAAAAAAAABGIAAAAAAJAAAAAAAAwAAAAAAGOAAAAAAAAAAAAIOAAAAAABwAAAAAAABAAAAAAAIwAAAAAAAAAAAAxAAAAAAAIAAAAAAAAGAAAAAAA4AAAAAAAAAAAAP4AAAAAAAAAAAAAAABwAAAAAAGOAAAAAAAAAAAAOAAAAAABwAAAAAAAAGAAAAAABAwAAAAAAAAAAAxAAAAAAAOAAAAAAAAAAAAAAAABwAAAAAAAAAAAGIAAAAAABwAAAAAAAAOAAAAAAAGIAAAAAAAAAAA/AAAAAAAAAAAAAAAABwAAAAAABxAAAAAAAAAAAFwAAAAAABEAAAAAAAAOAAAAAAAAGAAAAAAAAAAAGAAAAAAAAwAAAAAAABAAAAAAABAwAAAAAAAAABAwAAAAAAABAAAAAAAAwAAAAAAAIOAAAAAAAAAAAOAAAAAAAAPAAAAAAA4AAAAAAAABwAAAAAAAAAABwAAAAAAAAOAAAAAAwAAAAAAAAIGAAAAAAAAAAJGAAAAAAAAABwAAAJwAAAAAAAAAAwAAAAAAAAAAOwAAAAAAAAAJ2AA/AAAAAAAAAAOGAAAAAAAAAAAwAAAAAAAAAAAAAIAAAAAAAAAABxAAAAAAAAAAAGIAAAAAAAAABJPxIAAAAAAAAAAHAAAAAAAAAAAAwwAAAAAAAAO8AAAH4AAAAAAAAJ+AAAAAAAAAAABOAAAAAAAJ+AAAAAAHAAAAAAAAAwAAAAAAAAAAAGAAAAAAAJwAAAAAAAAwAAAAAAGIAAAAAAAAAAABxwAAAAAOAAAAAAAAAA4AAAAAJwAAAAAAAAAAAAIGAAAABOAAAAAAAAAAAIAAAABBwAAAAAAAAAAAAGAAAABOAAAAAAAAAAAAIAAAAPIAAAAAAAAAAAAAJ4AAAOAAAAAAAAAAAAAwAAAAOAAAAAAAAAAAAABxwAAJAAAAAAAAAAAAAAwAAI5AAAAAAAAAAAAAAJGAABwAAAAAAAAAAAAAAAABwwAAAAAAAAAAAAAAGPABAAAAAAAAAAAAAAAOABB4AAAAAAAAAAAAAABOGAOAAAAAAAAAAAAAAAIAHAAAAAAAAAAAAAAAABPGAwAAAAAAAAAAAAAAJAGOAAAAAAAAAAAAAAAAAHGBwAAAAAAAAAAAAAHAGGAAAAAAAAAAAAAAAAABGGAwAAAAAAAAAAAAGAGHAAAAAAAAAAAAAAAAAAAGGAGAAAAAAAAAAA3BGHAAAAAAAAAAAAAAAAAAABOOAB4AAAAAAAAP4JxOAAAAAAAAAAAAAAAAAAAABPB4BOwAAAAAI/AAxwAAAAAAAAAAAAAAAAAAAAAAIxPAAG+/wBtuQCTiMAAAAAAAAAAAAAAAAAAAAAAACPgdgAAAAACB8TwAAAAAAAAAAAAAAAAAAAAAAAAAD8ANsSCD98AeAAAAAAAAAAAAAAAAAAAAAAAAAAAAR8AAAAATuAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACXv9twAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAf/8QAHxEAAgICAwEBAQAAAAAAAAAAAREAUCBAEDBggJBw/9oACAEDAQE/EP1vcfU4759Cii6HcPFRdiiwdo8FprB2i234tfa4s3m7I+7PA3DwPWDvPA3B3m8NGPOHgbQ7zeHgd5vDomjPjTwLsaB4FSaQbAohpjYFqNgWot1rCuWa1jwPFnZO0447E8DTcfU68aLoh4s8DZPA98Ns/wAbPA9weBvnyh+UF4tYOvXLxXeorp5KKLFRRdSslg49B4K2WTjjyceSu13qLwKiiiiiii/XP//EAB8RAAIBBQEBAQEAAAAAAAAAAAERABAgMEBQYJBwgP/aAAgBAgEBPxD63qLEou8r3HHHgXYVrjyO1dh6bsXUe2vhEemr1+Wih3BQ+sOcUO4c47g4Zzjxoodo5xxDsChzjuDRHDHjRQ9s6Aodg6I4h2DwjpnYPVOweqeu9Y853vWFD4sbI2lFFsDZFDpqLEuedFcI9xZxQ7IoffHbHvjuih4h8oKHfHlB/KD8W7Fz3VWvO4+0rnHHa448T6TsUWgrH1ncoorlFFa+287j8C4444444/rn/8QALBABAAECAwgCAwEBAQEBAAAAAREAITFBYRBAUXGBkaHBMLFQ0fAg8eGQYP/aAAgBAQABPxD/AOk2DBzawBcxU2HbV4EA1M//AIN6YYqgKmQ3jrxapYbyGPE1JmUkfeWPFTkS5fUhWfxxXumpa1TtEYKU7PNo+qgIsZIPNQMUZfYIaijjiPFmoVA0neVcvNBTpj+bUCoAxWpVj9XrgO9cKe+5t4qY34KhyMD/ABBwbmB4KjL5xfalf2MtJpOPPKjXT+4zmVdInW+6kkIZoO5NaAmYfO064LioSo4jou7c71xU43jxPNccdYxzMTr+WHHsjAGq1LuVpWeuP8vTAlsLR0Mes7Y9cZPnYmoAdm592D7qKTGbR2gUIWHMz3+GU3ZEnmpSUZEvpUxkEB6GpQ0z+8jxNKBzF687MUMSI6lQ5BbKdcHr3o4ujL4hn0n8lhdp2OVkvOubkeKilLPYg9t9gBFwQtSbO7H1nA80OFf53DpFYbgiUMmPNSpPD0m53qbPlK1zOpsQG0ohHnTLwWjYvrqvrR/LcWTgmJ+Pd5Id34DOnnqtgjV6Lc9mDCGY06ROf8jt3o+HZQTzcV57scMq8IS9h+9asqOQoGjH6a7MxqDjomZo0zPtsFPbxQySfjLUZwPVXr6rMMZgHAMjYfPNySxoZGr2o9zENVcXeUERJHEaMnK8C/qZcztS4YwW48RwTY87DEsnqzNKFj0o8PB0/E4XaNht2DaNevanbkSqyrWD60MNXga0YQtySV0M3V33C69JdcRyaVzqSW9HDX62Xa5eWPTrQK8LYo4J7/DqIgASrlTio3C+lfbPljVye21j26UIsbjLV4ab+gEBGyOdGC3cEdf17cKRIRCJCNO6tEweCZlSuIIrd+zg/hcLtPCbSWPMP8eWNYgHh4/Y/VEXCwZvFc38EX9k4PN4au9JIAFCjJKTKq5kMxMxpwLcZv8Ao4P4RzdRCMHDrx7bMSmwMfY+KjcGyX4S4xQYBMnXg9OTl1QIRMmsMpZHUDUCsXWOctT8EMHnfefr/wAp25Eqsq1IHhVb9jkUM8MBm8V4r+GKxdIMDM0edh5aIDLRqeaPsRaQOD+Adaxj3yhzq4ZB4DINCmaQBw1dCiAMk3zXlw3I+R+JfamInfwtS0HVU9U+CTgE3aaBADgjO5WN7YYmTR++eyPvCVczry157/hdp0nIwNsz0NOew7InQl8s5uL0Nwx64RClEQtc6OJ8Ugir+Au9WnjViqV/wLSHEDtSY1xU85bxVzZ1x0cHpuCIjDWDiVj3FmfA6mH/AGnwKEGEeNAQscQ5cj9zv2H4IRvnOuB14bMfxmEtiByMXpx3CAuCS99x0+qQ8pAeAYHwijIwlMKWwmS0czR71g+nTDRMnT50+BduTlycGmasKxEslL9cAZvPmY0FQ4DZHB3wmrCsALrS0o0jJwPfNaiu2erRscEc5c350tCu3/RxcueCIiqyrn8hiWCVY9PBrDaJeYRx+ew4oYYZerDma7L7JzJuv6bck3y1ukwbg/bbo7J3AZnP44d/nS0rRbrnzcu9KqqyufzPjDSOsamVBsANmPzTYmvTgmo3qGkx68Hkl6kLsSzbDtQCiAYI3HelApgLq0oybFwNjvj1qAAharR6QA68Xmt/mzKBJyjq0j9tPRoYbgmOA68zqx58/n4aaAzzfZ22TsuFnFV+y50N6mvWVxPLZcnmL1nofOSzrEZssdDcTfzTZIyVAIBIOQOjPzRvwwXmD0Yp0CbskYSvFZ3A+G9XXpg631HfZ7vHzwQdPmaKUbgBLTJ3rpLY6G5NKSAaNvIO/wA9pCP4ZfR60hIiMiZUchIelbyHeHDAFVyKUFkGcibHaKn+Qe8PBFYfMzaAjqB8LubSMdXCTyPnhJwZ9B9PTZiNAb1PJ53izh1ivRdmAYC6qXwO/wA6guGeQn63NRWLrkgfnxKAaSRRzQ4OCVO8HHOJPJ53iJW4DQR/NNkokK+Uw8fOnpT+25yt4+puENkdAD2WsRbsTNASSCR3eCWxJql+o2FBRJ8wJ8/O45jnkh+tzcsmWeQF+twjkszdV+hsk9kNahD5N3vnII6HpViJEeSDcEFT3FF4Hc0gJ7CQ8jcLHXeeiPpsn5l85u5f2ftFWxmXvH1uB6SgcRs0ajcvEmz1IdySEEW6j5fG4WT/AEo97Lp4PcP1u2kgtKnij5r+0RHvcXtLEOQxdS3TcUCkmzVisKoJ4s3qy7ho14A+tljduaBJ42SNxPncQt4Oc1knJvV/2TgmSaJfcHxGCTFw9B13GAf1GznZvLu14/1Dse5WQw8MH8nhSJCIRIR+YnBYeCcGrRlBYcBuLhP6lsN3+Zd21OTxQhHBrTDfT63M4hijY1H8mkwtAIR4PyFlNsLHA4ulQZ1jms1c3cuWl3ts5NPzuxuVPX6RSrzbHu7pQwGWK2kZ88anqTnuW+sfhFuRABKtGPijDz+DzQF+gcBuczm5Prso4XpBfe72niFOSz7qY2AU6LHvdSLHAgejSKAviXs80qtln/XHxTEpzK87caQApyI7m1L5tP8AgndoIHDe+9cukbrce/SgX0bJIMR0Afvd7EwAeH2qwBSOYzTgyEcknd9NGElOypx/8KSkql5YM/oDeLdcUXYPeyaCFXqJ4Td4UMVeaT7bLjSDmf8AhuqgSsBm04jhmk7TNOpc/itTFuV/doRuDo/dNfybgNMAk8T9RWvAiHjdohZ6ibqIZSqAocIdqA3eTCeiG77NmCgi0P2u+5YzViA6tKAXMed6Gn2VYfafoUiZLJE7Yf7BlWCQlPgJ01AowzjPd+6ikn/w3HpO5KBVgLrSyEnLE28RVqZh7w+t4yGhnJSz3ijLRIRyasdKf4cI67hklFQKXmmEdv449q4D1wuQwOnyioRRMEpmG8+UNMR5o2UcUhc2G4INgntDtM9NmAg01P0PfebLHRVz5k6U6kc0Bmmonkgk/MfENprmr1jyqwuNq3LNxFBES4mVO5fGMvTnesIVq+Hg6fNb4gPjYWVkcpt4A9d5zmBnQ7P3suARQ8w8ydvkRmBKrAFIo4Ix0cJr240iIqsq57oTuzMFqe6sOO4u+zX5ARAAlXKmPZ6J28CetSswZ8DN6EtQojIyAg3mNeSFzZPRhpLD7skYa7IoAwPRh+MK7qEAGbTTCg7L/mGe7qlKQZ6PE0rIg4v/AEPr47duCYg4u1upsz7tvWehvawScwwBj3Ie+yaWxJiwW6jzPxNgfEYGRo87y+t8oz0eI1AjHlX/AFOXxYsIhwUfI+ApwSABitRkLgM3d/XTeycECVyOHe51p8SkTESpFZU+Bw6HxNCJJc+Bmi3iuv2frnvapOYbZp+qGJLHp1MPgAsMsWP6B5TZiVjMlsh0x6G+wkVsBbN74852Y3gXN8IemD04/wC4GsSZ+B7dCnvIuzXfHta6rb9Dhzj/AGathDABi0d1LwyOfNxpmpAsVbBUYSSJm4/rkb6LPVGUOTUj+w9mjjT7dMZy5lWPYOI5jqNv9LOaMDb9hbpvooIiMiZUclW7iMOov3/1k+xDDJ14unPZi5Caczow58t/e5oALhnzPrlswXXC4YZyZP8A5QiCMjgn+O+akWL0Je2/rF8cmAuLvbq/5mTJIz4nQpGBnrpxameXAYOL6NaHgGbIN/QREkcqfEAQFs/k4f8Amw7T0Kwcepl2/wARMiWjBd/Q6b+KIjCYNX0H4898eu2G18M1kGrTVRNg2yQo+xBpU4FDxgQcchofv8CTSEOGpqUHjgDbic+JTlkSBhGiyWJWizNeJ12yzRtXZMejv8IATyrSixP4kRBxwu7ODrYxUISCe5q+NlqGyGNm1fB+DE6GxL8Q9lT3NZyOQcxo7xFoUZ0ZBcMAcTXifxR/mh5BwSlcHTvy+J5N9RmugPFZFWaFhDxwH3sCAcusV1Gm39mmWxLokLi4jhw48sfwjCESmXfB4jwpcTOgyRzKdQIlCuI1j5ULfo+21OZ7wwvp1rHn6OOpxNd6MbExWNHvDnRglixXiubsJ7huK4Bm1HZOtPnifrYhFEkhfF/k0CAAgAsH4a3s1Fc9nErNDh4HEaGGShBXxGjxGuPOj/8AyuQaOxbyqV4jlTMU3gW9TPmdqRSBEsjlu2hTCOa4FONDcjf5e3agAAIDANiqAUru1eBrS2XFrIuAe9iCNgKHgeA0z+wQAEAFj8QKR7hZOKyaXIdZW5cDsES9LrtTiq0UksjpZmptPkHgdTJ61AKV5Fprj7TT9qQhCbljscJI5ysUhm/InXiekVglBIDYbQZXAGrRMfBFZy5823Okc9MsrVpz/Qcq0KVBXEarxfB+LBz0HkGpWewFZ9w0x505ciESEaY78rCOjUlGCDZ9nM7NccMSjR4PPa3I9ACHovTuRjk7P1Umjsj6MOtIkCJiPyacGb6VJNWP0b2lRJzPP5Pc0FSIGA6G0EoFphl2D70p4RLNmefF57JW0uR5z6xolEEHvyGR+OsAWwY8n/WtJxXYuOtk6OwKUZ2nRME51b0WD7/TtXFnh3AxP8GIs5J7sakVF1TtKsd5PsD6qTgtEeSsI/l86xJ+T+6G/s819YH7rwNfqaj4HP2MVCKvAA/dRaOzXwbUWBcBAdDaCIAXVyq3nZkg64DzWVjJ0U7j4NNi49iWV0KZcxBe8y5F+VEp2BwH5A2gwORNSoqFviNpn9NKjRViCeULbL0AFEdSpMh4Y7XuaMkLH6F7CteDFDt8qgVQDFakwhmeDB1rxi/p+6VRtneFbYdYyAJVpG57wZDTJ1qMoZHljLkW/KCj2BkejT66vBI/To1OoWn6mJ2pEBExHLYWBcFCdaiwdkg83qLCs1D3UDJah+yoj+xZTWEnk+wrAOoCk8f6ta98z6rFE8H+qkJnTDylXw7w9APdSw613vKmGGc14YbeMqpUObgdaz16+TcDzRmzIbp1fX5khlH/AAV6l22RO4QnmppM5JezDUgZrfb4wSk4AS1D3z/2EKgszBfQfdRTTiDsg7zRPCYYHQ/PIhJiJI1JqTiCu5DU+rP9rq8RD0FBUs2OaPdGd/FrWVnMaG8BfZag3RkHgqFUDPwqxUWJp/Q/+uP/2Q==";

const openImagePreview = (image) => {
    previewImage.value = image || DUMMY_IMAGE;
};

const closeImagePreview = () => {
    previewImage.value = null;
};

const isValidCoordinate = (value) => {
    const num = Number(value);
    return (
        value !== null &&
        value !== undefined &&
        value !== "" &&
        Number.isFinite(num) &&
        num !== 0
    );
};

const hasValidCoords = (row) =>
    isValidCoordinate(row.check_in_latitude) &&
    isValidCoordinate(row.check_in_longitude);

const mapModalVisible = ref(false);
const selectedVisitLocation = ref(null);
let mapInstance = null;
let mapMarker = null;
let mapResizeTimer = null;

// The modal container is re-created by v-if on every open, so any previous
// map instance must be fully torn down before a new one is created.
const destroyVisitMap = () => {
    if (mapResizeTimer) {
        clearTimeout(mapResizeTimer);
        mapResizeTimer = null;
    }
    if (mapMarker) {
        mapMarker.setMap(null);
        mapMarker = null;
    }
    if (mapInstance) {
        google.maps.event.clearInstanceListeners(mapInstance);
        mapInstance = null;
    }
};

const renderVisitMap = () => {
    const container = document.getElementById("check-in-map");
    if (!container || !selectedVisitLocation.value) return;

    if (typeof google === "undefined" || !google.maps) {
        Swal.fire("Error", "Map service is not available right now.", "error");
        return;
    }

    destroyVisitMap();

    const position = {
        lat: selectedVisitLocation.value.latitude,
        lng: selectedVisitLocation.value.longitude,
    };

    mapInstance = new google.maps.Map(container, {
        center: position,
        zoom: 16,
        mapTypeControl: false,
        streetViewControl: false,
    });

    mapMarker = new google.maps.Marker({
        position: position,
        map: mapInstance,
        title: selectedVisitLocation.value.name || "Check-in Location",
    });

    // Recalculate size once the modal is visible so tiles/layout render
    // correctly (Google Maps equivalent of Leaflet's invalidateSize()).
    mapResizeTimer = setTimeout(() => {
        mapResizeTimer = null;
        if (!mapInstance) return;
        google.maps.event.trigger(mapInstance, "resize");
        mapInstance.setCenter(position);
    }, 100);
};

const openCheckInMap = async (row) => {
    if (!hasValidCoords(row)) return;

    selectedVisitLocation.value = {
        latitude: Number(row.check_in_latitude),
        longitude: Number(row.check_in_longitude),
        name:
            row.check_in_location && row.check_in_location !== "-"
                ? row.check_in_location
                : "N/A",
    };
    mapModalVisible.value = true;
    await nextTick();
    renderVisitMap();
};

const closeCheckInMap = () => {
    mapModalVisible.value = false;
    destroyVisitMap();
};

onBeforeUnmount(() => {
    destroyVisitMap();
});

const now = new Date();
const selectedYear = ref(now.getFullYear());
const selectedMonth = ref(now.getMonth() + 1);

const yearOptions = computed(() => {
    const current = now.getFullYear();
    const years = [];
    for (let y = current - 5; y <= current + 5; y++) {
        years.push(y);
    }
    return years;
});

const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const formatDateISO = (d) => d.toISOString().split("T")[0];
const selectedDate = ref(formatDateISO(now));
const selectedEmployeeId = ref(null);
const selectedDepartmentId = ref(null);
const selectedOutletCode = ref(null);
const selectedStatus = ref("");

const allReportData = ref([]);

const dateMin = computed(() => {
    const d = new Date(selectedYear.value, selectedMonth.value - 1, 1);
    return d.toISOString().split("T")[0];
});

const dateMax = computed(() => {
    const d = new Date(selectedYear.value, selectedMonth.value, 0);
    return d.toISOString().split("T")[0];
});

const selectedDayName = computed(() => {
    if (!selectedDate.value) return "";
    const d = new Date(selectedDate.value + "T00:00:00");
    return d.toLocaleDateString("en-US", { weekday: "long" });
});

const parseOutletInfo = (scheduledOutletVisit) => {
    if (!scheduledOutletVisit || scheduledOutletVisit === "-") {
        return { code: "-", name: "-" };
    }
    const separator = " / ";
    const lastIdx = scheduledOutletVisit.lastIndexOf(separator);
    if (lastIdx === -1) {
        return { code: scheduledOutletVisit, name: scheduledOutletVisit };
    }
    return {
        code: scheduledOutletVisit.substring(lastIdx + separator.length).trim(),
        name: scheduledOutletVisit.substring(0, lastIdx).trim(),
    };
};

const enrichedReport = computed(() => {
    return allReportData.value.map((row) => {
        const outlet = parseOutletInfo(row.scheduled_outlet_visit);
        return {
            ...row,
            outlet_code: outlet.code,
            outlet_name: outlet.name,
        };
    });
});

const getId = (value) => {
    if (
        value === null ||
        value === undefined ||
        value === "" ||
        value === "-"
    ) {
        return null;
    }

    if (typeof value === "object") {
        return (
            value.id ??
            value.employee_id ??
            value.employeeID ??
            value.value ??
            null
        );
    }

    return value;
};

watch(selectedDepartmentId, (newDepartmentId, oldDepartmentId) => {
    const newId = getId(newDepartmentId);
    const oldId = getId(oldDepartmentId);

    if (newId !== oldId) {
        selectedEmployeeId.value = null;
    }
});

const filteredReport = computed(() => {
    const selectedDateValue = selectedDate.value;
    const departmentId = getId(selectedDepartmentId.value);
    const employeeId = getId(selectedEmployeeId.value);

    let data = [...enrichedReport.value];

    // Step 1: Exact Date Filter
    if (selectedDateValue) {
        data = data.filter((row) => {
            return (
                String(row.date_raw).trim() === String(selectedDateValue).trim()
            );
        });
    }

    // Step 2: Selected Department Filter
    if (departmentId !== null) {
        data = data.filter((row) => {
            return Number(row.department_id) === Number(departmentId);
        });
    }

    // Step 3: Selected Employee Filter
    // এটি আগের Date + Department filtered data-এর উপর apply হবে
    if (employeeId !== null) {
        data = data.filter((row) => {
            return Number(row.employee_id) === Number(employeeId);
        });
    }

    // Step 4: Outlet Filter
    if (selectedOutletCode.value?.code) {
        data = data.filter((row) => {
            return (
                String(row.outlet_code).trim() ===
                String(selectedOutletCode.value.code).trim()
            );
        });
    }

    // Step 5: Status Filter
    if (selectedStatus.value) {
        data = data.filter((row) => {
            return (
                String(row.status).trim() ===
                String(selectedStatus.value).trim()
            );
        });
    }

    return data;
});

const visitedCount = computed(() => {
    return filteredReport.value.filter((row) => row.status === "Visited")
        .length;
});

const notVisitedCount = computed(() => {
    return filteredReport.value.filter((row) => row.status === "Not Visited")
        .length;
});

let fetchSeq = 0;

const fetchReport = async () => {
    const seq = ++fetchSeq;
    loading.value = true;
    try {
        const res = await api.get("/outlet-visit-report", {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
            },
        });
        if (seq !== fetchSeq) return;
        allReportData.value = res.data?.data?.report ?? [];
    } catch (err) {
        if (seq !== fetchSeq) return;
        const message = err.response?.data?.message || "Failed to load report.";
        Swal.fire("Error", message, "error");
    } finally {
        if (seq === fetchSeq) loading.value = false;
    }
};

watch([selectedYear, selectedMonth], () => {
    selectedEmployeeId.value = null;
    selectedDepartmentId.value = null;
    selectedOutletCode.value = null;
    selectedStatus.value = "";

    if (
        selectedYear.value === now.getFullYear() &&
        selectedMonth.value === now.getMonth() + 1
    ) {
        selectedDate.value = formatDateISO(now);
    } else {
        const firstDay = new Date(
            selectedYear.value,
            selectedMonth.value - 1,
            1,
        );
        selectedDate.value = formatDateISO(firstDay);
    }
    fetchReport();
});

onMounted(() => {
    fetchReport();
});

const downloadReport = async () => {
    downloading.value = true;
    try {
        const res = await api.get("/outlet-visit-report", {
            params: {
                year: selectedYear.value,
                month: selectedMonth.value,
                date: selectedDate.value || null,
                department_id: getId(selectedDepartmentId.value),
                employee_id: getId(selectedEmployeeId.value),
                download: "csv",
            },
            responseType: "blob",
        });

        const blob = new Blob([res.data], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download =
            "outlet_visit_report_" +
            selectedYear.value +
            "_" +
            String(selectedMonth.value).padStart(2, "0") +
            ".csv";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    } catch (err) {
        const message =
            err.response?.data?.message || "Failed to download report.";
        Swal.fire("Error", message, "error");
    } finally {
        downloading.value = false;
    }
};
</script>

<template>
    <div class="report-wrapper">
        <!-- Top Header & Global Controls -->
        <div class="mb-4">
            <div class="p-1">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div
                            class="icon-shape bg-success-subtle text-success rounded-3 p-3 d-flex align-items-center justify-content-center"
                        >
                            <i class="fa-solid fa-calendar-days fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-dark">
                                Schedule Outlet Visit Report
                            </h4>
                            <p class="text-muted small mb-0">
                                Track and manage employee outlet visits and
                                schedule analytics.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="control-group">
                            <div class="select-wrapper">
                                <select
                                    class="form-select year-select"
                                    v-model="selectedYear"
                                >
                                    <option
                                        v-for="y in yearOptions"
                                        :key="y"
                                        :value="y"
                                    >
                                        {{ y }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <div class="select-wrapper">
                                <select
                                    class="form-select month-select"
                                    v-model="selectedMonth"
                                >
                                    <option
                                        v-for="(m, idx) in monthNames"
                                        :key="idx"
                                        :value="idx + 1"
                                    >
                                        {{ m }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="align-self-end">
                            <button
                                class="btn btn-sm btn-success btn-download d-inline-flex align-items-center gap-2 px-3"
                                @click="downloadReport"
                                :disabled="downloading"
                            >
                                <i
                                    class="fa-solid"
                                    :class="
                                        downloading
                                            ? 'fa-spinner fa-spin'
                                            : 'fa-download'
                                    "
                                ></i>
                                <span>{{
                                    downloading
                                        ? "Exporting..."
                                        : "Export Excel"
                                }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls Panel -->
        <div class="mb-2 mt-5">
            <div class="p-1">
                <div class="row g-3">
                    <div class="d-flex gap-3">
                        <div class="">
                            <DateField v-model="selectedDate" type="date" />
                        </div>
                        <div class="">
                            <DayName v-model="selectedDayName" />
                        </div>
                        <div class="">
                            <BaseDept
                                v-model:departmentID="selectedDepartmentId"
                                :userID="userID"
                            />
                        </div>
                        <div class="">
                            <!-- <EmployeeName
                                v-model:employee_id="selectedEmployeeId"
                            /> -->
                            <BaseDeptEmp
                                v-model:employeeID="selectedEmployeeId"
                                :deptID="getId(selectedDepartmentId)"
                                :isEdit="false"
                            />
                        </div>
                        <div class="">
                            <select
                                class="form-select status-select"
                                v-model="selectedStatus"
                            >
                                <option value="-" disabled>
                                    Select Status
                                </option>
                                <option value="">All Status</option>
                                <option value="Visited">Visited</option>
                                <option value="Not Visited">Not Visited</option>
                            </select>
                            <!-- <SelectField
                                v-model="selectedStatus"
                                :items="[
                                    { name: 'All Status', value: '' },
                                    { name: 'Visited', value: 'Visited' },
                                    {
                                        name: 'Not Visited',
                                        value: 'Not Visited',
                                    },
                                ]"
                            /> -->
                        </div>
                    </div>
                    <div
                        v-if="!selectedStatus"
                        class="report-summaries col-md-4"
                    >
                        <div
                            class="summary-box d-flex align-items-center justify-content-between h-38 px-3"
                        >
                            <div class="d-flex align-items-center gap-4 p-2">
                                <div>
                                    <span class="summary-label"
                                        >Total Assign</span
                                    >
                                    <span class="summary-value ms-2">
                                        {{ filteredReport.length }}
                                    </span>
                                </div>

                                <div class="vr"></div>

                                <div>
                                    <span class="summary-label">Visited</span>
                                    <span class="summary-value ms-2">
                                        {{ visitedCount }}
                                    </span>
                                </div>

                                <div class="vr"></div>

                                <div>
                                    <span class="summary-label"
                                        >Not Visited</span
                                    >
                                    <span class="summary-value ms-2">
                                        {{ notVisitedCount }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="">
            <div class="table-responsive d-lg-block">
                <table
                    class="table table-bordered table-sm custom-payroll-table"
                >
                    <thead>
                        <tr class="text-nowrap">
                            <th
                                scope="col"
                                class="text-center"
                                style="width: 3%"
                            >
                                #
                            </th>
                            <th class="text-nowrap" scope="col">Name</th>
                            <th scope="col">Department</th>
                            <th scope="col">Code</th>
                            <th scope="col">Outlet Name</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">Check in Location</th>
                            <th scope="col">in Time</th>
                            <th scope="col">out Time</th>
                            <th scope="col">image</th>
                            <th
                                scope="col"
                                class="text-center"
                                style="width: 120px"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="loading">
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div
                                        class="d-flex flex-column align-items-center justify-content-center"
                                    >
                                        <div
                                            class="spinner-border text-success mb-2"
                                            role="status"
                                            style="width: 2rem; height: 2rem"
                                        ></div>
                                        <span class="text-muted small"
                                            >Fetching report data...</span
                                        >
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else-if="filteredReport.length === 0">
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="empty-state text-muted">
                                        <i
                                            class="fa-regular fa-folder-open display-6 mb-2 text-secondary opacity-50"
                                        ></i>
                                        <p class="mb-0 small fw-medium">
                                            No schedule visit records found for
                                            the selected filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr
                                v-for="(row, idx) in filteredReport"
                                :key="`${row.date_raw}-${row.employee_id}-${row.outlet_code}-${row.distributor_name}`"
                            >
                                <td class="text-center text-muted fw-semibold">
                                    {{ idx + 1 }}
                                </td>
                                <td class="text-nowrap">
                                    {{ row.employee_name || "-" }}
                                </td>
                                <td>{{ row.department || "-" }}</td>

                                <td>{{ row.outlet_code }}</td>
                                <td>
                                    {{ row.outlet_name }}
                                </td>
                                <td>
                                    {{ row.remarks }}
                                </td>
                                <td>
                                    <div
                                        class="d-flex align-items-center gap-1"
                                    >
                                        <span
                                            class="fw-semibold text-dark"
                                            style="max-width: 180px"
                                            :title="row.check_in_location"
                                        >
                                            <i
                                                class="fa-solid fa-location-dot text-danger me-1 small"
                                                v-if="
                                                    row.check_in_location &&
                                                    row.check_in_location !==
                                                        '-'
                                                "
                                            ></i>
                                            {{ row.check_in_location || "-" }}
                                        </span>
                                        <button
                                            v-if="hasValidCoords(row)"
                                            type="button"
                                            class="btn-map-view"
                                            title="View on Map"
                                            @click="openCheckInMap(row)"
                                        >
                                            <i
                                                class="fa-solid fa-map-location-dot"
                                            ></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-semibold text-dark">
                                        {{ row.check_in || "-" }}
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-semibold text-dark">
                                        {{ row.check_out || "-" }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <img
                                        :src="row.picture || DUMMY_IMAGE"
                                        :class="[
                                            'report-thumb',
                                            {
                                                'report-thumb--dummy':
                                                    !row.picture,
                                            },
                                        ]"
                                        height="40"
                                        width="40"
                                        @click="openImagePreview(row.picture)"
                                    />
                                </td>
                                <td class="text-center">
                                    <span
                                        class="status-pill d-inline-flex align-items-center gap-1"
                                        :class="
                                            row.status === 'Visited'
                                                ? 'status-visited'
                                                : 'status-not-visited'
                                        "
                                    >
                                        <span class="status-dot"></span>
                                        {{ row.status }}
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Image Preview Overlay -->
        <teleport to="body">
            <div
                v-if="previewImage"
                class="image-preview-overlay"
                @click.self="closeImagePreview"
            >
                <div class="image-preview-container">
                    <button
                        class="image-preview-close"
                        @click="closeImagePreview"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <img
                        :src="previewImage"
                        class="image-preview-img"
                        @error="previewImage = DUMMY_IMAGE"
                    />
                </div>
            </div>
        </teleport>
        <!-- Check-in Location Map Modal -->
        <teleport to="body">
            <div
                v-if="mapModalVisible"
                class="image-preview-overlay"
                @click.self="closeCheckInMap"
            >
                <div class="map-modal-card">
                    <div class="map-modal-header">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i
                                class="fa-solid fa-location-dot text-danger me-2"
                            ></i>
                            Check-in Location
                        </h6>
                        <button
                            type="button"
                            class="map-modal-close"
                            title="Close"
                            @click="closeCheckInMap"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div
                        v-if="selectedVisitLocation"
                        id="check-in-map"
                        class="map-modal-canvas"
                    ></div>
                    <div v-if="selectedVisitLocation" class="map-modal-details">
                        <span>
                            Latitude:
                            <strong>{{
                                selectedVisitLocation.latitude.toFixed(7)
                            }}</strong>
                        </span>
                        <span class="ms-3">
                            Longitude:
                            <strong>{{
                                selectedVisitLocation.longitude.toFixed(7)
                            }}</strong>
                        </span>
                        <span class="ms-3">
                            Location:
                            <strong>{{ selectedVisitLocation.name }}</strong>
                        </span>
                    </div>
                    <div class="map-modal-footer">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary px-3"
                            @click="closeCheckInMap"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>

<style scoped>
.report-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
}

.select-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.month-select {
    padding-left: 2.25rem;
    padding-right: 2.5rem;
    height: 30px;
    border-radius: 0.5rem;
    border: 1px solid #d0d5dd;
    font-size: 14px;
}
.year-select {
    padding-left: 1rem;
    padding-right: 3rem;
    height: 30px;
    border-radius: 0.5rem;
    border: 1px solid #d0d5dd;
    font-size: 14px;
}

.icon-shape {
    width: 48px;
    height: 48px;
}

.btn-download {
    border-radius: 8px;
    font-size: 0.84rem;
    font-weight: 600;
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
    background-color: #10b981;
    border-color: #10b981;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
    transition: all 0.2s ease;
}

.btn-download:hover:not(:disabled) {
    background-color: #059669;
    border-color: #059669;
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
}
.custom-payroll-table thead th {
    background-color: #423434;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-top: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eaecf0;
}

.custom-payroll-table tbody td {
    /* padding-top: 6px;
    padding-bottom: 6px; */
    font-size: 0.875rem;
}

/* .custom-payroll-table tbody tr:hover td {
    background-color: #7e6666;
    color: #fff;
    font-size: 11px;
} */

.status-pill {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.status-visited {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.status-visited .status-dot {
    background-color: #10b981;
}

.status-not-visited {
    background-color: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.status-not-visited .status-dot {
    background-color: #ef4444;
}

.report-thumb {
    border-radius: 50%;
    cursor: pointer;
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
    object-fit: cover;
}

.report-thumb:hover {
    transform: scale(1.15);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.4);
}

.report-thumb--dummy {
    opacity: 0.55;
}

.btn-map-view {
    border: 1px solid #0d6efd;
    background-color: #eff5ff;
    color: #0d6efd;
    border-radius: 5px;
    font-size: 0.7rem;
    width: 22px;
    height: 22px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    flex-shrink: 0;
    transition:
        background-color 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

.btn-map-view:hover {
    background-color: #0d6efd;
    color: #fff;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
}

.map-modal-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    width: min(640px, 92vw);
    overflow: hidden;
}

.map-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eaecf0;
}

.map-modal-close {
    border: none;
    background: transparent;
    color: #333;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition:
        background-color 0.15s ease,
        color 0.15s ease;
}

.map-modal-close:hover {
    background-color: #ef4444;
    color: #fff;
}

.map-modal-canvas {
    height: 340px;
    width: 100%;
}

.map-modal-details {
    padding: 0.65rem 1rem;
    font-size: 0.82rem;
    color: #212529;
    border-top: 1px solid #eaecf0;
}

.map-modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.65rem 1rem;
    border-top: 1px solid #eaecf0;
}

.image-preview-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.image-preview-container {
    position: relative;
    max-width: 90vw;
    max-height: 85vh;
}

.image-preview-img {
    display: block;
    max-width: 90vw;
    max-height: 85vh;
    border-radius: 12px;
    object-fit: contain;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.image-preview-close {
    position: absolute;
    top: -12px;
    right: -12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #fff;
    color: #333;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
    transition: background 0.15s ease;
    z-index: 10;
}

.image-preview-close:hover {
    background: #ef4444;
    color: #fff;
}

.summary-box {
    background: #f1f3f5;
    border: 0.8px solid rgb(196, 194, 194);
    border-radius: 5px;
}

.summary-label {
    font-size: 0.78rem;
    color: #868e96;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #212529;
}

.status-select {
    width: 100px;
    font-size: 14px;
    border: 0.8px solid rgb(196, 194, 194);
    padding: 5px;
    border-radius: 5px;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none !important;
}
</style>
