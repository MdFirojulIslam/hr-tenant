<script setup>
import { onMounted } from "vue";

onMounted(() => {});
</script>

<template>
    <div class="border rounded h-100 d-flex flex-column p-0 m-0">
        <div class="p-3">
            <div class="d-flex justify-content-between">
                <div class="header"><h3>Incentive Setup Form</h3></div>
            </div>
            <div class="mt-3">
                <div class="row">
                    <div class="col-lg-2">
                        <div>
                            <label class="form-label" for=""
                                >Select Month Period</label
                            >
                        </div>
                        <div>
                            <select>
                                <option value="1">June 2026</option>
                                <option value="2">July 2026</option>
                                <option value="3">Agust 2026</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div>
                            <label class="form-label" for=""
                                >Select Department</label
                            >
                        </div>
                        <select name="" id="">
                            <option value="1">department one</option>
                            <option value="2">department two</option>
                            <option value="3">department three</option>
                        </select>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="table-responsive shadow-sm bg-light">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr class="text-nowrap text-center">
                                    <th><input type="checkbox" /></th>
                                    <th>SL</th>
                                    <th>EMPLOYEE NAME</th>
                                    <th>DEPARTMENT</th>
                                    <th>DESIGNATION</th>
                                    <th>INCENTIVE AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-nowrap">
                                    <td class="text-center">
                                        <input type="checkbox" />
                                    </td>
                                    <td colspan="5">
                                        Infomation and Communitcation Technology
                                    </td>
                                </tr>
                                <tr class="text-nowrap text-center">
                                    <td><input type="checkbox" /></td>
                                    <td>1</td>
                                    <td>firoj 1</td>
                                    <td>
                                        Infomation and Communitcation Technology
                                    </td>
                                    <td>Junior Software Engineer</td>
                                    <td><input type="number" /></td>
                                </tr>
                                <tr class="text-nowrap text-center">
                                    <td><input type="checkbox" /></td>
                                    <td>2</td>
                                    <td>firoj 2</td>
                                    <td>
                                        Infomation and Communitcation Technology
                                    </td>
                                    <td>Junior Software Engineer</td>
                                    <td><input type="number" /></td>
                                </tr>
                                <tr class="text-nowrap text-center">
                                    <td><input type="checkbox" /></td>
                                    <td>3</td>
                                    <td>firoj 3</td>
                                    <td>
                                        Infomation and Communitcation Technology
                                    </td>
                                    <td>Junior Software Engineer</td>
                                    <td><input type="number" /></td>
                                </tr>
                                <tr class="text-nowrap">
                                    <td class="text-center">
                                        <input type="checkbox" />
                                    </td>
                                    <td colspan="5">General Trade</td>
                                </tr>
                                <tr class="text-nowrap text-center">
                                    <td><input type="checkbox" /></td>
                                    <td>4</td>
                                    <td>new 1</td>
                                    <td>General Trade</td>
                                    <td>Software Engineer</td>
                                    <td><input type="number" /></td>
                                </tr>
                                <tr class="text-nowrap text-center">
                                    <td><input type="checkbox" /></td>
                                    <td>5</td>
                                    <td>new 2</td>
                                    <td>General Trade</td>
                                    <td>Software Engineer</td>
                                    <td><input type="number" /></td>
                                </tr>
                                <tr class="text-nowrap text-center">
                                    <td><input type="checkbox" /></td>
                                    <td>6</td>
                                    <td>new 3</td>
                                    <td>General Trade</td>
                                    <td>Software Engineer</td>
                                    <td><input type="number" /></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-end gap-4">
                            <div>
                                <button type="button">Generate</button>
                            </div>
                            <div>
                                <button type="button">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
