const express = require("express");
const mongoose = require("mongoose");
const cors = require("cors");

const app = express();
app.use(cors());
app.use(express.json());

// ✅ CONNECT TO MONGODB (LOCAL)
mongoose
  .connect("mongodb://localhost:27017/globetrotter")
  .then(() => console.log("✅ MongoDB connected"))
  .catch(err => console.error(err));

// ✅ TEMP SCHEMA (NO STRICT RULES)
const Trip = mongoose.model(
  "Trip",
  new mongoose.Schema({}, { strict: false })
);

// ✅ API TO GET TRIPS
app.get("/api/trips", async (req, res) => {
  const trips = await Trip.find({});
  res.json(trips);
});


// ✅ START SERVER
app.listen(5000, () => {
  console.log("🚀 Backend running on http://localhost:5000");
});
