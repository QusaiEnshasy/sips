import { d as p } from "./index-eB-YqcqT.js";

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

const a = p.create({
  baseURL: "",
  headers: {
    "X-Requested-With": "XMLHttpRequest",
    "X-CSRF-TOKEN": csrf,
    Accept: "application/json"
  },
  withCredentials: true
});

const c = {
  getDashboard: () => a.get("/company/dashboard"),
  getPrograms: (params) => a.get("/company/programs", { params }),
  getProgram: (id) => a.get(`/company/programs/${id}`),
  createProgram: (data) => a.post("/company/programs", data),
  updateProgram: (id, data) => a.post(`/company/programs/${id}`, data),
  deleteProgram: (id) => a.post(`/company/programs/${id}/delete`),
  duplicateProgram: async (id) => {
    const payload = { ...(await a.get(`/company/programs/${id}`)).data?.data || {} };
    delete payload.id;
    payload.title = `${payload.title || "Program"} (Copy)`;
    return a.post("/company/programs", payload);
  },
  getApplicants: (params) => a.get("/company/applicants", { params }),
  getApplicant: (id) => a.get(`/company/applicants/${id}`),
  acceptApplicant: (id) => a.post(`/company/applications/${id}/approve`),
  rejectApplicant: (id, data) => a.post(`/company/applications/${id}/reject`, data || {}),
  getReports: () => a.get("/company/reports/data"),

  getTrelloSettings: () => a.get("/company/trello/settings"),
  saveTrelloSettings: (data) => a.post("/company/trello/settings", data),
  testTrelloConnection: () => a.post("/company/trello/test"),
  getTrelloBoards: () => a.get("/company/trello/boards"),
  getTrelloLists: (boardId) => a.get(`/company/trello/boards/${boardId}/lists`),
  getTrelloIntegrations: () => a.get("/company/trello/integrations"),
  connectTrelloBoard: (internshipId, data) => a.post(`/company/trello/internships/${internshipId}/connect`, data),
  syncTrello: (internshipId) => a.post(`/company/trello/internships/${internshipId}/sync`),
  disconnectTrello: () => a.delete("/company/trello/disconnect")
};

export { c };
