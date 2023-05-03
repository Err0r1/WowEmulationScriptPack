#pragma once

struct ItemTemplate;

bool IsTransmogItem(const ItemTemplate *it);
bool HasTransmog(unsigned int AcctId, unsigned int ItemId);
void AddTransmog(unsigned int AcctId, unsigned int ItemId);
